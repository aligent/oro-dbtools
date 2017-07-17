<?php
namespace Aligent\DBToolsBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

/**
 * Create Command - Creates an empty database.
 *
 * @category  Aligent
 * @package   DBToolsBundle
 * @author    Adam Hall <adam.hall@aligent.com.au>
 * @copyright 2017 Aligent Consulting.
 * @license   https://opensource.org/licenses/mit MIT License
 * @link      http://www.aligent.com.au/
 **/

class CreateCommand extends AbstractCommand
{
    const COMMAND_NAME = 'oro:db:create';
    const COMMAND_DESCRIPTION=  'Creates an empty database.';

    public function configure() {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Prints only the command. Does not Execute.')
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output) {
        $query = 'CREATE DATABASE IF NOT EXISTS `' . $this->database->settings->getName() . '`';

        if ($input->getOption('only-command')) {
            $output->writeln($query);
        } else {
            $db = $this->database->getConnection();
            $db->query($query);
            $output->writeln('<info>Created database</info> <comment>' . $this->database->settings->getName() . '</comment>');
        }
    }
}