<?php
namespace Aligent\DBToolsBundle\Command;

use Aligent\DBToolsBundle\Database\DatabaseConnectionInterface;
use Aligent\DBToolsBundle\Helper\VerifyOrDie;
use Aligent\DBToolsBundle\Provider\DatabaseConnectionProvider;
use Aligent\DBToolsBundle\Sanitizer\Sanitizer;
use Ifsnop\Mysqldump\Mysqldump;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\VarDumper\VarDumper;

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
    const COMMAND_NAME = 'dump';
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
     * @var DatabaseConnectionProvider
     */
    protected $connectionProvider;

    /**
     * ConsoleCommand constructor.
     * @param DatabaseConnectionProvider $connectionProvider
     * @param array $definitions
     */
    public function __construct(
        DatabaseConnectionProvider $connectionProvider,
        array $definitions
    ) {
        $this->connectionProvider = $connectionProvider;
        $this->definitions = $definitions;
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
                'sanitize',
                's',
                InputOption::VALUE_REQUIRED,
                'Which sanitization definition to use.'
            )
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
            ->addOption('exclude', null, InputOption::VALUE_OPTIONAL, 'Comma separated list of tables to exclude from the dump')
            ->addOption('include', null, InputOption::VALUE_OPTIONAL, 'Comma separated list of tables to include in the dump');;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);

        $this->connection = $this->connectionProvider->getConnection();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dumpSettings = [];

        $compression = $input->getOption('compression');
        if ($compression) {
            if (strtolower($compression) !== strtolower(Mysqldump::GZIP)) {
                throw new \InvalidArgumentException("$compression is not a supported.");
            }

            $dumpSettings['compress'] = Mysqldump::GZIP;
        }

        // If we're sanitizing get which definition to use
        $sanitizer = $input->getOption('sanitize');
        if ($sanitizer) {
            if (!isset($this->definitions[$sanitizer])) {
                throw new \InvalidArgumentException($sanitizer . ' does not exist. Check for valid values using the list-definitions command.');
            }

            $definition = $this->definitions[$sanitizer];
            $dumpSettings['no-data'] = $definition['truncate'] ?? [];
        }

        if ($input->getOption('exclude')) {
            $dumpSettings['exclude-tables'] = explode(',', $input->getOption('exclude'));
            if ($output->isVerbose()) {
                $output->writeln(
                    sprintf('<comment>Excluded: <info>%s</info></comment>', implode(', ', $dumpSettings['exclude-tables']))
                );
            }
        }

        if ($input->getOption('include')) {
            $dumpSettings['include-tables'] = explode(',', $input->getOption('include'));

            if ($output->isVerbose()) {
                $output->writeln(
                    sprintf('<comment>Included: <info>%s</info></comment>', implode(', ', $dumpSettings['include-tables']))
                );
            }
        }

        // Fetch the configured dumper from our connection
        $dumper = $this->connection->getDumper($dumpSettings);

        if ($sanitizer) {
            $updates = $definition['update'] ?? [];
            $sanitizer = new Sanitizer();

            $dumper->setTransformTableRowHook(
                function ($tableName, array $row) use ($sanitizer, $updates) {
                    if (isset($updates[$tableName])) {
                        $columns = $updates[$tableName]['columns'] ?? [];
                        foreach ($columns as $column => $function) {
                            if (!array_key_exists($column, $row)) {
                                throw new \InvalidArgumentException("$column does not exist on $tableName, cannot sanitize a column that does not exist.");
                            }

                            if (isset($row[$column])) {
                                $row[$column] = $sanitizer->sanitize($function, $row[$column]);
                            }
                        }
                    }

                    return $row;
                }
            );
        }

        $dumper->setInfoHook(
            function ($object, $info) use ($output) {
                if ($output->isVeryVerbose()) {
                    if ($object === 'table') {
                        $output->writeln("Dumped Table " . $info['name'] . ", Rows: " . $info['rowCount']);
                    }
                }
            }
        );

        $fileName = $this->getFileName($input, $output);
        $dumper->start($fileName);
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

            if($input->getOption('compression')) {
                if (substr($defaultName, -3, 3) === '.gz') {
                    return $defaultName;
                } elseif (substr($defaultName, -4, 4) === '.sql') {
                    $defaultName .= '.gz';
                } else {
                    $defaultName .= '.sql.gz';
                }
            }

            /** @var QuestionHelper $helper */
            $helper = $this->getHelper('question');
            $question = new Question( '<question>Filename for SQL dump:</question> [<comment>' . $defaultName . '</comment>]', $defaultName);
            $fileName = $helper->ask($input, $output, $question);
        } else {
            if ($input->getOption('add-time')) {
                $pathParts = pathinfo($fileName);
                $fileName = ($pathParts['dirname'] == '.' ? '' : $pathParts['dirname'] . DIRECTORY_SEPARATOR) .
                    $namePrefix . $pathParts['filename'] . $nameSuffix . '.' . $pathParts['extension'];
            }
        }

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
