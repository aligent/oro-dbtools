<?php
/**
 * Created by PhpStorm.
 * User: adam.hall
 * Date: 7/13/17
 * Time: 9:29 AM
 */

namespace Aligent\DBToolsBundle\Command;

use Aligent\DBToolsBundle\Helper\Compressor\Compressor;
use Aligent\DBToolsBundle\Helper\VerifyOrDie;

use InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Yaml\Yaml;

class DumpCommand extends AbstractCommand
{
    const COMMAND_NAME = 'oro:db:dump';
    const COMMAND_DESCRIPTION=  'Dumps the database';

    protected $tableDefinitions;

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addArgument('filename', InputArgument::OPTIONAL, 'Dump filename')
            ->addOption(
                'add-time',
                't',
                InputOption::VALUE_OPTIONAL,
                'Adds time to filename (only if filename was not provided)'
            )
            ->addOption(
                'compression',
                null,
                InputOption::VALUE_REQUIRED,
                'Compress the dump file using one of the supported algorithms'
            )
            ->addOption(
                'only-command',
                null,
                InputOption::VALUE_NONE,
                'Print only mysqldump command. Do not execute'
            )
            ->addOption(
                'strip',
                null,
                InputOption::VALUE_OPTIONAL,
                'Tables to strip (dump only structure of those tables)'
            )
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Do not prompt if all options are defined')
            ->addOption('exclude', null, InputOption::VALUE_OPTIONAL, 'Tables to exclude from the dump')
            ->addOption('include', null, InputOption::VALUE_OPTIONAL, 'Tables to include in the dump');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output); // Parent init first we need db deets
        $this->loadTableDefinitions(); // Get table alias groupings from table_groups.yml
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $compressor = $this->getCompressor($input->getOption('compression'));
        $fileName = $this->getFileName($input, $output, $compressor);

        $canOutputInformation = !$input->getOption('only-command'); //add others back later

        if ($canOutputInformation) {
            $this->writeSection($output, 'Dump MySQL Database');
        }

        // strip tables (structure, no data)
        $stripTables = array();
        if ($input->getOption('strip')) {
            $stripTables = $this->database->resolveTables(
                explode(' ', $input->getOption('strip')),
                $this->tableDefinitions
            );

            if ($canOutputInformation) {
                $output->writeln(
                    sprintf('<comment>No-data export for: <info>%s</info></comment>', implode(' ', $stripTables))
                );
            }
        }

        if ($input->getOption('exclude') && $input->getOption('include')) {
            throw new InvalidArgumentException('Cannot specify --include with --exclude');
        }

        $excludeTables = array();
        if ($input->getOption('exclude')) {
            $excludeTables = $this->database->resolveTables(
                explode(' ', $input->getOption('exclude')),
                $this->tableDefinitions
            );
            if ($canOutputInformation) {
                $output->writeln(
                    sprintf('<comment>Excluded: <info>%s</info></comment>', implode(' ', $excludeTables))
                );
            }
        }

        if ($input->getOption('include')) {
            $includeTables = $this->database->resolveTables(
                explode(' ', $input->getOption('include')),
                $this->tableDefinitions
            );
            $excludeTables = array_diff($this->database->getTables(), $includeTables);
            if ($canOutputInformation) {
                $output->writeln(
                    sprintf('<comment>Included: <info>%s</info></comment>', implode(' ', $includeTables))
                );
            }
        }

        $commands = array();

        //Ignore the strips because we will be dumping just their structure later
        $ignore = '';
        foreach (array_merge($excludeTables, $stripTables) as $tableName) {
            $ignore .= '--ignore-table=' . $this->database->settings->getName() . '.' . $tableName . ' ';
        }

        // dump structure for strip-tables
        if (count($stripTables) > 0) {
            $stripCommand = $this->database->getMysqlConnectionString('mysqldump', [
                '--no-data'
            ]);
            $stripCommand .= ' ' . implode(' ', $stripTables);
            $stripCommand .= $this->postDumpPipeCommands();
            $stripCommand = $compressor->getCompressingCommand($stripCommand);
            $stripCommand .= ' > ' . escapeshellarg($fileName);

            $commands[] = $stripCommand;
        }

        //dump the rest
        $dumpCommand = $this->database->getMysqlConnectionString('mysqldump') . ' ' . $ignore;
        $dumpCommand .= $this->postDumpPipeCommands();
        $dumpCommand = $compressor->getCompressingCommand($dumpCommand);
        $dumpCommand .= (count($stripTables) > 0 ? ' >> ' : ' > ') . escapeshellarg($fileName);
        $commands[] = $dumpCommand;

        foreach ($commands as $command) {
            if ($input->getOption('only-command')) {
                $output->writeln($command);
            } else {
                $this->processCommand($command);
            }
        }
    }

    /**
     * Commands which filter mysql data. Piped to mysqldump command
     *
     * @return string
     */
    protected function postDumpPipeCommands()
    {
        return ' | LANG=C LC_CTYPE=C LC_ALL=C sed -e ' . escapeshellarg('s/DEFINER[ ]*=[ ]*[^*]*\*/\*/');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param Compressor $compressor
     *
     * @return string
     */
    protected function getFileName(InputInterface $input, OutputInterface $output, Compressor $compressor)
    {
        $namePrefix = '';
        $nameSuffix = '';
        $nameExtension = '.sql';

        if ($input->getOption('add-time') !== false) {
            $timeStamp = date('Y-m-d_His');

            if ($input->getOption('add-time') == 'suffix') {
                $nameSuffix = '_' . $timeStamp;
            } else {
                $namePrefix = $timeStamp . '_';
            }
        }

        if (($fileName = $input->getArgument('filename')) === null || ($isDir = is_dir($fileName))) {

            $defaultName = VerifyOrDie::filename(
                $namePrefix . $this->database->settings->getName() . $nameSuffix . $nameExtension
            );
            if (isset($isDir) && $isDir) {
                $defaultName = rtrim($fileName, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $defaultName;
            }
            if (!$input->getOption('force')) {
                /** @var QuestionHelper $helper */
                $helper = $this->getHelper('question');
                $question = new Question( '<question>Filename for SQL dump:</question> [<comment>' . $defaultName . '</comment>]', $defaultName);
                $fileName = $helper->ask($input, $output, $question);
            } else {
                $fileName = $defaultName;
            }
        } else {
            if ($input->getOption('add-time')) {
                $pathParts = pathinfo($fileName);
                $fileName = ($pathParts['dirname'] == '.' ? '' : $pathParts['dirname'] . DIRECTORY_SEPARATOR) .
                    $namePrefix . $pathParts['filename'] . $nameSuffix . '.' . $pathParts['extension'];
            }
        }

        $fileName = $compressor->getFileName($fileName);

        return $fileName;
    }

    private function loadTableDefinitions() {
        $filePath = $this->getContainer()->get('kernel')->locateResource('@AligentDBToolsBundle/Resources/config/table_groups.yml');

        if (file_exists($filePath)) {
            $tableDefinitions = Yaml::parse(file_get_contents($filePath));
            $this->tableDefinitions = $tableDefinitions === null ? array() : $tableDefinitions;
        } else {
            throw new FileNotFoundException("table_groups.yml could not be found at " . $filePath);
        }
    }
}