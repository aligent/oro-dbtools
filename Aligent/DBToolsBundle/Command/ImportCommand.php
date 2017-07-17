<?php
namespace Aligent\DBToolsBundle\Command;

use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
class ImportCommand extends AbstractCommand
{

    const COMMAND_NAME = 'oro:db:import';
    const COMMAND_DESCRIPTION=  'opens mysql client by database config';

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
                'Convert verbose INSERTs to short ones before import (not working with compression)'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->writeSection($output, 'Import MySQL Database');

        $compressor = $this->getCompressor($input->getOption('compression'));
        $fileName = $this->checkFilename($input);

        if ($input->getOption('optimize')) {
            if ($input->getOption('only-command')) {
                throw new InvalidArgumentException('Options --only-command and --optimize are not compatible');
            }
            if ($input->getOption('compression')) {
                throw new InvalidArgumentException('Options --compression and --optimize are not compatible');
            }
            $output->writeln('<comment>Optimizing <info>' . $fileName . '</info> to temporary file');
            $fileName = $this->optimize($fileName);
        }


        // create import command
        $command = $compressor->getDecompressingCommand(
            $this->database->getMysqlConnectionString('mysql'),
            $fileName
        );

        if ($input->getOption('only-command')) {
            $output->writeln($command);
            return;
        } else {
            if ($input->getOption('only-if-empty') && count($this->database->getTables()) > 0 ) {
                $output->writeln('<comment>Skip import. Database is not empty</comment>');
                return;
            }
        }

//        if ($input->getOption('drop')) {
//            $dbHelper->dropDatabase($output);
//            $dbHelper->createDatabase($output);
//        }
//        if ($input->getOption('drop-tables')) {
//            $dbHelper->dropTables($output);
//        }

        $output->writeln(
            '<comment>Importing SQL dump <info>' . $fileName . '</info> to database <info>'
            . $this->database->settings->getName() . '</info>'
        );

        $this->processCommand($command);

        $output->writeln('<info>Finished</info>');

        if ($input->getOption('optimize')) {
            unlink($fileName);
        }
    }

    /**
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
