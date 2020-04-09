<?php
namespace Aligent\DBToolsBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
/**
 * List Definitions Command - Lists Available sanitization definitions
 *
 * @category  Aligent
 * @package   DBToolsBundle
 * @author    Adam Hall <adam.hall@aligent.com.au>
 * @copyright 2017 Aligent Consulting.
 * @license   https://opensource.org/licenses/mit MIT License
 * @link      http://www.aligent.com.au/
 **/
class ListDefinitionsCommand extends Command
{
    const COMMAND_NAME = 'list-definitions';
    const COMMAND_DESCRIPTION=  'Lists Available sanitization definitions';
    /**
     * @var array
     */
    protected $definitions;

    /**
     * ListDefinitionsCommand constructor.
     * @param array $definitions
     */
    public function __construct(
        array $definitions
    ) {
        $this->definitions = $definitions;
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
        $table = new Table($output);
        $table->setHeaders(['Definitions']);

        foreach ($this->definitions as $name => $definition) {
            $table->addRow([$name]);
        }

        $table->render();
    }
}