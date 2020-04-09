<?php
namespace Aligent\DBToolsBundle\Command;

use Aligent\DBToolsBundle\Provider\DatabaseConnectionProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
/**
 * Info Command - Displays info on the database connection Oro is using.
 *
 * @category  Aligent
 * @package   DBToolsBundle
 * @author    Adam Hall <adam.hall@aligent.com.au>
 * @copyright 2017 Aligent Consulting.
 * @license   https://opensource.org/licenses/mit MIT License
 * @link      http://www.aligent.com.au/
 **/
class InfoCommand extends Command
{
    const COMMAND_NAME = 'info';
    const COMMAND_DESCRIPTION=  'Dumps the database connection details.';

    /**
     * @var DatabaseConnectionProvider
     */
    protected $connectionProvider;

    /**
     * ConsoleCommand constructor.
     * @param DatabaseConnectionProvider $connectionProvider
     */
    public function __construct(
        DatabaseConnectionProvider $connectionProvider
    ) {
        $this->connectionProvider = $connectionProvider;
        parent::__construct();
    }

    /**
     * Configures the name, arguments and options of the command
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->connectionProvider->getConnection();
        $data = [
            ['host', $connection->getHost()],
            ['port', $connection->getPort()],
            ['dbname', $connection->getName()],
            ['username', $connection->getUser()],
            ['password', $connection->getPassword()],
            ['PDO-Connection-String', $connection->getPdoConnectionString()],
            ['JBDC-Connection-String', $connection->getJbdcConnectionString()],
            ['cli-String', $connection->getConnectionString()]
        ];

        $table = new Table($output);
        $table->setHeaders(['Name', 'Value'])
            ->setRows($data);
        $table->render();
    }
}