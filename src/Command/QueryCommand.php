<?php
namespace Aligent\DBToolsBundle\Command;

use Aligent\DBToolsBundle\Provider\DatabaseConnectionProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Process\Process;

/**
 * Query Command - Runs a query on the mysql database that Oro is using.
 *
 * @category  Aligent
 * @package   DBToolsBundle
 * @author    Adam Hall <adam.hall@aligent.com.au>
 * @copyright 2017 Aligent Consulting.
 * @license   https://opensource.org/licenses/mit MIT License
 * @link      http://www.aligent.com.au/
 **/

class QueryCommand extends Command
{
    const COMMAND_NAME = 'query';
    const COMMAND_DESCRIPTION=  'Performs a query on the mysql database';

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
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addArgument('query', InputArgument::OPTIONAL, 'SQL query')
            ->addOption('only-command', null, InputOption::VALUE_NONE, 'Prints only the command. Does not Execute.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    public function execute(InputInterface $input, OutputInterface $output) {

        $query = $input->getArgument('query');
        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new Question( '<question>SQL:</question>[<comment>' . $query . '</comment>]', $query);
        $query = $helper->ask($input, $output, $question);

        $connection = $this->connectionProvider->getConnection();
        $command = $connection->getConnectionString(
            'mysql',
            [
                '-e\'' . $query . '\''
            ]
        );

        if ($input->getOption('only-command')) {
            $output->writeln($command);
        } else {
            $process = new Process($command);
            $process->mustRun();
            $output->writeln($process->getOutput());
        }
    }
}