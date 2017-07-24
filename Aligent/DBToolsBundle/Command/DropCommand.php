<?php
namespace Aligent\DBToolsBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;


/**
 * Drop Command - Drops the database or tables from the database.
 *
 * @category  Aligent
 * @package   DBToolsBundle
 * @author    Adam Hall <adam.hall@aligent.com.au>
 * @copyright 2017 Aligent Consulting.
 * @license   https://opensource.org/licenses/mit MIT License
 * @link      http://www.aligent.com.au/
 **/

class DropCommand extends AbstractCommand
{
    const COMMAND_NAME = 'oro:db:drop';
    const COMMAND_DESCRIPTION=  'Drops the current Database';

    private $dropped = 0;

    public function configure() {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addOption('tables', 't', InputOption::VALUE_NONE, 'Drop all tables instead of dropping the database')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force')
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Prints only the command. Does not Execute.')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output) {

        if ($input->getOption('force')) {
            $shouldDrop = true;
        } else {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you sure?', false);
            $shouldDrop = $helper->ask($input, $output, $question);
        }

        if ($shouldDrop) {
            if ($input->getOption('tables')) {
                $query = $this->dropTables();
                $output->writeln('<info>Dropping Tables</info>');
            } else {
                $query = $this->dropDatabase();
                $output->writeln('<info>Dropping database</info> <comment>' . $this->database->settings->getName() . '</comment>');
            }

            if ($input->getOption('only-command')) {
                $output->writeln($query);
            } else {
                $db = $this->database->getConnection();
                $db->query($query);

                if ($input->getOption('tables')) {
                    $output->writeln('<info>Dropped tables</info> <comment>' . $this->dropped . ' tables dropped</comment>');
                } else {
                    $output->writeln('<info>Dropped Database</info> <comment>' . $this->database->settings->getName() . '</comment>');

                }
            }
        }
    }

    private function dropTables() {
        $query = 'SET FOREIGN_KEY_CHECKS = 0; ';
        $tables = $this->database->getTables();

        $this->dropped = 0;
        foreach ($tables as $table) {
            $query .= 'DROP TABLE IF EXISTS ' . $this->database->settings->getName() . ".$table;";
            $this->dropped++;
        }

        $query .= 'SET FOREIGN_KEY_CHECKS = 1; ';
        return $query;
    }

    private function dropDatabase() {
        return 'DROP DATABASE `' . $this->database->settings->getName() . '`;';
    }
}