<?php
/**
 * Created by PhpStorm.
 * User: adam.hall
 * Date: 7/13/17
 * Time: 9:29 AM
 */

namespace Aligent\DBToolsBundle\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends AbstractCommand
{
    const COMMAND_NAME = 'oro:db:info';
    const COMMAND_DESCRIPTION=  'Dumps the database connection details.';

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $data = array(
            array('host', $this->database->settings->getHost()),
            array('port', $this->database->settings->getPort()),
            array('dbname', $this->database->settings->getName()),
            array('username', $this->database->settings->getUser()),
            array('password', $this->database->settings->getPassword()),
            array('PDO-Connection-String', $this->database->getPdoConnectionString()),
            array('JBDC-Connection-String', $this->database->getJbdcConnectionString()),
            array('MySQL-Cli-String', $this->database->getMysqlConnectionString())
        );

        $table = new Table($output);
        $table->setHeaders(array('Name', 'Value'))
            ->setRows($data);
        $table->render();
    }
}