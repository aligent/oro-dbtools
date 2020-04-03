<?php
namespace Aligent\DBToolsBundle\Command;

use Aligent\DBToolsBundle\Database\MysqlConnection;
use Aligent\DBToolsBundle\Helper\Compressor\Compressor;
use Aligent\DBToolsBundle\Provider\CompressionServiceProvider;
use Aligent\DBToolsBundle\Provider\DatabaseConnectionProvider;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Import Command - Imports the database from a file
 *
 * @category  Aligent
 * @package   DBToolsBundle
 * @author    Adam Hall <adam.hall@aligent.com.au>
 * @copyright 2017 Aligent Consulting.
 * @license   https://opensource.org/licenses/mit MIT License
 * @link      http://www.aligent.com.au/
 **/
class ImportCommand extends Command
{

    const COMMAND_NAME = 'oro:db:import';
    const COMMAND_DESCRIPTION=  'opens mysql client by database config';

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
     */
    public function __construct(
        DatabaseConnectionProvider $connectionProvider,
        CompressionServiceProvider $compressionProvider
    ) {
        $this->connectionProvider = $connectionProvider;
        $this->compressionProvider = $compressionProvider;
        parent::__construct();
    }

    /**
     * Configures the name, arguments and options of the command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addArgument('filename', InputArgument::REQUIRED, 'Dump filename')
            ->addOption('compression', 'c', InputOption::VALUE_REQUIRED, 'The compression of the specified file')
            ->addOption("only-command", null, InputOption::VALUE_NONE, "Prints the command. Does not execute.")
            ->addOption('only-if-empty', null, InputOption::VALUE_NONE, 'Imports only if database is empty')
            ->addOption(
                'optimize',
                null,
                InputOption::VALUE_NONE,
                'Convert verbose INSERTs to short ones before import (not working with compression or postgres)'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            '',
            $this->getHelper('formatter')->formatBlock('Import SQL Database', 'bg=blue;fg=white', true),
            '',
        ]);

        $connection = $this->connectionProvider->getConnection();
        $fileName = $this->checkFilename($input);

        if ($input->getOption('compression')) {
            $compressor = $this->compressionProvider->getCompressor($input->getOption('compression'));
        } else {
            $compressor = $this->compressionProvider->getCompressor('none');
        }

        if ($input->getOption('optimize')) {
            if ($input->getOption('only-command')) {
                throw new InvalidArgumentException('Options --only-command and --optimize are not compatible');
            }
            if ($input->getOption('compression')) {
                throw new InvalidArgumentException('Options --compression and --optimize are not compatible');
            }
            if (!$connection instanceof MysqlConnection) {
                throw new InvalidArgumentException('--optimize is currently supportord for mysql connections');
            }

            $output->writeln('<comment>Optimizing <info>' . $fileName . '</info> to temporary file');
            $fileName = $this->optimize($fileName);
        }

        // create import command
        $command = $compressor->getDecompressingCommand(
            $connection->getConnectionString(),
            $fileName
        );

        if ($input->getOption('only-command')) {
            $output->writeln($command);
            return;
        } else {
            if ($input->getOption('only-if-empty') && count($connection->getTables()) > 0 ) {
                $output->writeln('<comment>Skip import. Database is not empty</comment>');
                return;
            }
        }

        $output->writeln(
            '<comment>Importing SQL dump <info>' . $fileName . '</info> to database <info>'
            . $connection->getName() . '</info>'
        );

        // need nulls to remove timeout
        // @TODO: Default timeout to null but allow option to be passed through
        $process = new Process(
            $command,
            null,
            null,
            null,
            null
        );

        $process->mustRun();
        
        $output->writeln('<info>Finished</info>');

        if ($input->getOption('optimize')) {
            unlink($fileName);
        }
    }

    /**
     * @todo pull out to service or connection so we can support postgres
     * Optimize a dump by converting single INSERTs per line to INSERTs with multiple lines
     * @param $fileName
     * @return string temporary filename
     */
    protected function optimize($fileName)
    {
        $in = fopen($fileName, 'r');
        $result = tempnam(sys_get_temp_dir(), 'dump') . '.sql';
        $out = fopen($result, 'w');

        fwrite($out, 'SET autocommit=0;' . "\n");
        $currentTable = '';
        $maxlen = 8 * 1024 * 1024; // 8 MB
        $len = 0;
        while ($line = fgets($in)) {
            if (strtolower(substr($line, 0, 11)) == 'insert into') {
                preg_match('/^insert into `(.*)` \([^)]*\) values (.*);/i', $line, $m);

                if (count($m) < 3) { // fallback for very long lines or other cases where the preg_match fails
                    if ($currentTable != '') {
                        fwrite($out, ";\n");
                    }
                    fwrite($out, $line);
                    $currentTable = '';
                    continue;
                }

                $table = $m[1];
                $values = $m[2];

                if ($table != $currentTable || ($len > $maxlen - 1000)) {
                    if ($currentTable != '') {
                        fwrite($out, ";\n");
                    }
                    $currentTable = $table;
                    $insert = 'INSERT INTO `' . $table . '` VALUES ' . $values;
                    fwrite($out, $insert);
                    $len = strlen($insert);
                } else {
                    fwrite($out, ',' . $values);
                    $len += strlen($values) + 1;
                }
            } else {
                if ($currentTable != '') {
                    fwrite($out, ";\n");
                    $currentTable = '';
                }
                fwrite($out, $line);
            }
        }

        fwrite($out, ";\n");

        fwrite($out, 'COMMIT;' . "\n");

        fclose($in);
        fclose($out);

        return $result;
    }

    /**
     * @param InputInterface $input
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    protected function checkFilename(InputInterface $input)
    {
        $fileName = $input->getArgument('filename');
        if (!file_exists($fileName)) {
            throw new InvalidArgumentException('File does not exist');
        }
        return $fileName;
    }
}
