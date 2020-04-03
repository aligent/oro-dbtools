<?php
namespace Aligent\DBToolsBundle\Command;

use Aligent\DBToolsBundle\Database\DatabaseConnectionInterface;
use Aligent\DBToolsBundle\Database\MysqlConnection;
use Aligent\DBToolsBundle\Helper\Compressor\Compressor;
use Aligent\DBToolsBundle\Helper\VerifyOrDie;

use Aligent\DBToolsBundle\Provider\CompressionServiceProvider;
use Aligent\DBToolsBundle\Provider\DatabaseConnectionProvider;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Exception\FileNotFoundException;
use Symfony\Component\Process\Process;
use Symfony\Component\VarDumper\VarDumper;
use Symfony\Component\Yaml\Yaml;

/**
 * Dump Command - Dumps the database to a file
 *
 * @category  Aligent
 * @package   DBToolsBundle
 * @author    Adam Hall <adam.hall@aligent.com.au>
 * @copyright 2017 Aligent Consulting.
 * @license   https://opensource.org/licenses/mit MIT License
 * @link      http://www.aligent.com.au/
 **/

class DumpCommand extends Command
{
    const COMMAND_NAME = 'oro:db:dump';
    const COMMAND_DESCRIPTION=  'Dumps the database';

    /**
     * @var array
     */
    protected $definitions;

    /**
     * @var DatabaseConnectionInterface
     */
    protected $connection;

    /**
     * @var Compressor
     */
    protected $compressor;

    /**
     * @var DatabaseConnectionProvider
     */
    protected $connectionProvider;

    /**
     * @var CompressionServiceProvider
     */
    protected $compressionProvider;

    /**
     * ConsoleCommand constructor.
     * @param DatabaseConnectionProvider $connectionProvider
     * @param CompressionServiceProvider $compressionProvider
     * @param array $definitions
     */
    public function __construct(
        DatabaseConnectionProvider $connectionProvider,
        CompressionServiceProvider $compressionProvider,
        array $definitions
    ) {
        $this->connectionProvider = $connectionProvider;
        $this->compressionProvider = $compressionProvider;
        $this->tableDefinitions = $definitions;
        parent::__construct();
    }

    /**
     * Configures the name, arguments and options of the command
     */
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
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        if ($input->getOption('compression')) {
            $this->compressor = $this->compressionProvider->getCompressor($input->getOption('compression'));
        } else {
            $this->compressor = $this->compressionProvider->getCompressor('none');
        }

        $this->connection = $this->connectionProvider->getConnection();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $fileName = $this->getFileName($input, $output);
        $canOutputInformation = !$input->getOption('only-command');


        if ($canOutputInformation) {
            $this->writeSection($output, 'Dumping Database');
        }

        $dumpCommand = $compressor->getCompressingCommand($this->connection->getDumpDatabaseCommand());
        $dumpCommand .= ' > ' . escapeshellarg($fileName);

        if ($input->getOption('only-command')) {
            $output->writeln($dumpCommand);
        } else {
            $process = new Process(
                $dumpCommand,
                null,
                null,
                null,
                null
            );

            try {
                $process->mustRun();
                $output->writeln($process->getOutput());
            } catch (\Exception $e) {
                $output->writeln($process->getErrorOutput());
                $output->writeln($e->getMessage());
            }
        }
    }
    

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return string
     */
    protected function getFileName(InputInterface $input, OutputInterface $output)
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
                $namePrefix . $this->connection->getName() . $nameSuffix . $nameExtension
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

        $fileName = $this->compressor->getFileName($fileName);

        return $fileName;
    }

    /**
     * Helper function to output section blocks
     * @param OutputInterface $output
     * @param string $text
     * @param string $style
     */
    protected function writeSection(OutputInterface $output, $text, $style = 'bg=blue;fg=white')
    {
        $output->writeln(array(
            '',
            $this->getHelper('formatter')->formatBlock($text, $style, true),
            '',
        ));
    }
}
