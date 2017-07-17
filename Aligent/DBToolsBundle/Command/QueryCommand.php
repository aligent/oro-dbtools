<?php
/**
 * Created by PhpStorm.
 * User: adam.hall
 * Date: 7/17/17
 * Time: 9:58 AM
 */

namespace Aligent\DBToolsBundle\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class QueryCommand extends AbstractCommand
{
    const COMMAND_NAME = 'oro:db:query';
    const COMMAND_DESCRIPTION=  'Performs a query on the mysql database';

    public function configure() {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addArgument('query', InputArgument::OPTIONAL, 'SQL query')
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Prints only the command. Does not Execute.');
    }

    public function execute(InputInterface $input, OutputInterface $output) {

        $query = $input->getArgument('query');
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question( '<question>SQL:</question>[<comment>' . $query . '</comment>]', $query);
        $query = $helper->ask($input, $output, $question);

        $command = $this->database->getMysqlConnectionString('mysql', [
            '-e\'' . $query . '\''
        ]);

        if ($input->getOption('only-command')) {
            $output->writeln($command);
        } else {
            $this->processCommand($command);
        }
    }
}