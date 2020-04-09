<?php
/**
 *
 *
 * @category  Aligent
 * @package
 * @author    Adam Hall <adam.hall@aligent.com.au>
 * @copyright 2020 Aligent Consulting.
 * @license
 * @link      http://www.aligent.com.au/
 */

namespace Aligent\DBToolsBundle;

use Aligent\DBToolsBundle\Command\ConsoleCommand;
use Aligent\DBToolsBundle\Command\CreateCommand;
use Aligent\DBToolsBundle\Command\DropCommand;
use Aligent\DBToolsBundle\Command\DumpCommand;
use Aligent\DBToolsBundle\Command\ImportCommand;
use Aligent\DBToolsBundle\Command\InfoCommand;
use Aligent\DBToolsBundle\Command\ListDefinitionsCommand;
use Aligent\DBToolsBundle\Command\QueryCommand;
use Aligent\DBToolsBundle\Compressor\Gzip;
use Aligent\DBToolsBundle\Compressor\Uncompressed;
use Aligent\DBToolsBundle\Provider\CompressionServiceProvider;
use Aligent\DBToolsBundle\Provider\DatabaseConnectionProvider;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Exception\ExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;
use TypeError;

class Application extends BaseApplication
{
    const CONFIG_FILE_NAME = 'db-tools-config.yml';

    public $configuration = [
        'connection' => [
            'database_name'     => null,
            'database_user'     => null,
            'database_password' => null,
            'database_host'     => null,
            'database_port'     => null,
            'database_driver'   => null,
        ],
        'definitions' => []
    ];

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @param array $configuration
     */
    public function setConfiguration(array $configuration): void
    {
        $this->configuration = $configuration;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Throwable
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        $this
            ->getDefinition()
            ->addOptions(
                [
                    new InputOption(
                        'database-name',
                        'name',
                        InputOption::VALUE_OPTIONAL,
                        'The database name to operate on.'
                    ),
                    new InputOption(
                        'database-user',
                        'user',
                        InputOption::VALUE_OPTIONAL,
                        'The database user to authenticate with.'
                    ),
                    new InputOption(
                        'database-password',
                        'password',
                        InputOption::VALUE_OPTIONAL,
                        'The database users password.'
                    ),
                    new InputOption(
                        'database-host',
                        'host',
                        InputOption::VALUE_OPTIONAL,
                        'The hostname of the database.'
                    ),
                    new InputOption(
                        'database-port',
                        'port',
                        InputOption::VALUE_OPTIONAL,
                        'The port used to connect with the database.'
                    ),
                    new InputOption(
                        'database-driver',
                        'driver',
                        InputOption::VALUE_OPTIONAL,
                        'The database driver that should be used.'
                    ),
                    new InputOption(
                        'config',
                        null,
                        InputOption::VALUE_OPTIONAL,
                        'The file that should be used to configure this utility.'
                    )
                ]
            );

        try {
            // Makes ArgvInput::getFirstArgument() able to distinguish an option from an argument.
            $input->bind($this->getDefinition());
        } catch (ExceptionInterface $e) {
            // Errors must be ignored, full binding/validation happens later when the command is known.
        }

        $configFile = $input->getOption('config');
        $filesystem = new Filesystem();


        // Pull in our included sanitization definitions
        $this->configuration['definitions'] = Yaml::parseFile(realpath(__DIR__ . '/../resources/definitions.yml'));

        // if the user hasn't specified a config file check application root (one directory up from where this script exists)
        // and if it exists there use it
        // else if the user has specified a file and it doesn't exist throw an error
        if (!$configFile && $filesystem->exists(getcwd() . '/' . self::CONFIG_FILE_NAME)) {
            $configFile = getcwd() . '/' . self::CONFIG_FILE_NAME;
        } else if ($configFile && !$filesystem->exists($configFile)) {
            $output->writeln("<error>$configFile does not exist.</error>");
            exit();
        }

        // Merge in the config file
        if ($configFile) {
            $this->configuration = array_replace_recursive($this->configuration, Yaml::parseFile($configFile));
        }

        // attempt to pull connection configuration from environment variables
        // these will override anything configured in the yaml file
        foreach ($this->configuration['connection'] as $configKey => $configValue) {
            $env = getenv(strtoupper($configKey));
            if ($env) {
                $this->configuration['connection'][$configKey] = $env;
            }
        }

        // Initialize our database connection provider with out configured values
        // Configuration from our passed options  will override anything from above
        //@Todo: Add some config validation so we can get rid of this try/catch
        try {
            $connectionProvider = new DatabaseConnectionProvider(
                $input->getOption('database-name') ?? $this->configuration['connection']['database_name'],
                $input->getOption('database-user') ?? $this->configuration['connection']['database_user'],
                $input->getOption('database-password') ?? $this->configuration['connection']['database_password'],
                $input->getOption('database-host') ?? $this->configuration['connection']['database_host'],
                $input->getOption('database-port') ?? $this->configuration['connection']['database_port'],
                $input->getOption('database-driver') ?? $this->configuration['connection']['database_driver']
            );
        } catch (TypeError $error) {
            $output->writeln(
                "<error>The Database connection has been configured incorrectly. Please ensure you have correctly configured the database connection in yml, environment variables or with the --database-* options</error>"
            );
            $output->writeln($error->getMessage());
            exit();
        }

        // Add in our compression services
        // @TODO: Delegate Compression to mysql-dump library so we don't rely on platform binaries
        $compressionProvider = new CompressionServiceProvider();
        $compressionProvider->addCompressor(
            'gz',
            new Gzip()
        );
        $compressionProvider->addCompressor(
            'gzip',
            new Gzip()
        );
        $compressionProvider->addCompressor(
            'none',
            new Uncompressed()
        );

        // Finally Add in our commands with their dependencies
        $this->addCommands(
            [
                new ConsoleCommand($connectionProvider),
                new CreateCommand($connectionProvider),
                new DropCommand($connectionProvider),
                new InfoCommand($connectionProvider),
                new QueryCommand($connectionProvider),
                new DumpCommand($connectionProvider, $this->configuration['definitions']),
                new ImportCommand($connectionProvider, $compressionProvider),
                new ListDefinitionsCommand($this->configuration['definitions'])
            ]
        );

        return parent::doRun($input, $output);
    }
}