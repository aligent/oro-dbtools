<?php

namespace Aligent\DBToolsBundle\Command;

use Aligent\DBToolsBundle\Database\DatabaseConnectionInterface;
use Aligent\DBToolsBundle\Provider\DatabaseConnectionProvider;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

/**
 * Console Command - Opens a direct database console
 *
 * @category  Aligent
 * @package   DBToolsBundle
 * @author    Adam Hall <adam.hall@aligent.com.au>
 * @copyright 2017 Aligent Consulting.
 * @license   https://opensource.org/licenses/mit MIT License
 * @link      http://www.aligent.com.au/
 **/
class ConsoleCommand extends Command
{
    const COMMAND_NAME = 'oro:db:console';
    const COMMAND_DESCRIPTION = 'Opens database client using the currently configured database connection.';

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
        $this->setName(self::COMMAND_NAME)->setDescription(self::COMMAND_DESCRIPTION)
              ->addOption(
                  "only-command",
                  null,
                  InputOption::VALUE_NONE,
                  "Prints the command. Does not execute.");
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var DatabaseConnectionInterface $connection */
        $connection = $this->connectionProvider->getConnection();
        $exec = $connection->getConnectionString();

        if ($input->getOption('only-command')) {
            $output->writeln($exec);

            return;
        } else {
            // Need nulls to get rid of timeout
            $process = new Process(
                $exec,
                null,
                null,
                null,
                null,
                []
            );
            $process->setTty(true);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new \RuntimeException($process->getErrorOutput());
            }
        }

    }
}