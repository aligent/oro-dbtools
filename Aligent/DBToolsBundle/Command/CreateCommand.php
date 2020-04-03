<?php
namespace Aligent\DBToolsBundle\Command;

use Aligent\DBToolsBundle\Provider\DatabaseConnectionProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\VarDumper\VarDumper;

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

class CreateCommand extends Command
{
    const COMMAND_NAME = 'oro:db:create';
    const COMMAND_DESCRIPTION=  'Creates an empty database.';

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
    public function configure() {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addOption(
                'only-command',
                null,
                InputOption::VALUE_NONE,
                'Prints only the command. Does not Execute.'
            )
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    public function execute(InputInterface $input, OutputInterface $output) {
        $connection = $this->connectionProvider->getConnection();

        $outputOnly = (bool) $input->getOption('only-command');
        $query = $connection->getCreateDatabaseQuery();
        $output->writeln($query);

        if (!$outputOnly) {
            $pdo = $connection->getPDOConnection();
            $pdo->query($query);
            $output->writeln('<info>Created database</info> <comment>' . $connection->getName() . '</comment>');
        }
    }
}