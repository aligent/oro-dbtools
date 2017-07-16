<?php
/**
 * Created by PhpStorm.
 * User: adam.hall
 * Date: 7/13/17
 * Time: 9:29 AM
 */

namespace Aligent\DBToolsBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleCommand extends AbstractCommand
{
    const COMMAND_NAME = 'oro:db:console';
    const COMMAND_DESCRIPTION=  'opens mysql client by database config';

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addOption("only-command", null, InputOption::VALUE_NONE, "Prints the command. Does not execute.");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exec = $this->database->getMysqlConnectionString();

        if ($input->getOption('only-command')) {
            $output->writeln($exec);
            return;
        } else {
            $this->processCommand($exec);
        }

    }
}