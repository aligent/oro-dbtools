<?php

namespace Aligent\DBToolsBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * @category  Aligent
 * @package   DBToolsBundle
 * @author    Adam Hall <adam.hall@aligent.com.au>
 * @copyright 2017 Aligent Consulting.
 * @license   https://opensource.org/licenses/mit MIT License
 * @link      http://www.aligent.com.au/
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 **/

class Configuration implements ConfigurationInterface
{
    const DEFINITIONS = 'definitions';

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('aligent_db_tools');
        $rootNode = $treeBuilder->getRootNode();

        // Here you should define the parameters that are allowed to
        // configure your bundle. See the documentation linked above for
        // more information on that topic.
        $rootNode
            ->children()
                ->arrayNode(self::DEFINITIONS)
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->arrayNode('truncate')
                                ->children()
                                    ->arrayNode('standard')
                                        ->scalarPrototype()->end()
                                    ->end()
                                    ->arrayNode('cascade')
                                        ->scalarPrototype()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('update')
                                ->useAttributeAsKey('table')
                                ->arrayPrototype()
                                    ->arrayPrototype()
                                        ->children()
                                            ->scalarNode('column')->end()
                                            ->scalarNode('function')->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();


        return $treeBuilder;
    }
}
