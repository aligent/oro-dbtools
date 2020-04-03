<?php
namespace Aligent\DBToolsBundle\Command;

use Aligent\DBToolsBundle\Provider\DatabaseConnectionProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\VarDumper\VarDumper;


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

class DropCommand extends Command
{
    const COMMAND_NAME = 'oro:db:drop';
    const COMMAND_DESCRIPTION=  'Drops the current Database';
    
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
            ->addOption('tables', 't', InputOption::VALUE_NONE, 'Drop all tables instead of dropping the database')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force')
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Prints only the command. Does not Execute.')
        ;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    public function execute(InputInterface $input, OutputInterface $output) {

        if ($input->getOption('force')) {
            $shouldDrop = true;
        } else {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion('Are you sure?', false);
            $shouldDrop = $helper->ask($input, $output, $question);
        }

        if ($shouldDrop) {
            $connection = $this->connectionProvider->getConnection();

            if ($input->getOption('tables')) {
                $query = $connection->getDropTablesQuery();
                $output->writeln('<info>Dropping Tables</info>');
            } else {
                $query = $connection->getDropDatabaseQuery();
                $output->writeln('<info>Dropping database</info> <comment>' . $connection->getName() . '</comment>');
            }

            if ($input->getOption('only-command')) {
                $output->writeln($query);
            } else {
                $pdo = $connection->getPDOConnection();
                $return = $pdo->query($query);

                if ($input->getOption('tables')) {
                    $output->writeln('<info>Dropped tables</info>');
                } else {
                    $output->writeln('<info>Dropped Database</info> <comment>' . $connection->getName() . '</comment>');
                }
            }
        }
    }
}