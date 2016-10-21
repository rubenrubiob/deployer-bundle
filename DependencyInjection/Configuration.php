<?php

namespace Rrb\DeployerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('rrb_deployer');

        $rootNode
            ->children()
                ->scalarNode('fabric')
                    ->defaultValue('fab')
                ->end()
                ->integerNode('timeout')
                    ->min(60)->defaultValue(3600)
                ->end()
                ->integerNode('idle_timeout')
                    ->min(60)->defaultValue(600)
                ->end()
                ->arrayNode('hosts')
                ->isRequired()
                ->useAttributeAsKey('default')
                    ->prototype('array')
                        ->children()
                            ->arrayNode('environment')
                                ->children()
                                    ->scalarNode('php')
                                        ->defaultValue('php')
                                    ->end()
                                    ->scalarNode('src')
                                        ->info('This is the absolute path to the source code within server')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('env')
                                        ->defaultValue('prod')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('host')
                            ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('user')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->scalarNode('password')
                                        ->defaultValue(null)
                                    ->end()
                                    ->scalarNode('server')
                                        ->info('This is the domain name or the IP of the server to deploy to')
                                        ->isRequired()
                                        ->cannotBeEmpty()
                                    ->end()
                                    ->integerNode('port')
                                        ->min(1)->defaultValue(22)
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('git')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->scalarNode('remote')
                                        ->defaultValue('origin')
                                    ->end()
                                    ->scalarNode('branch')
                                        ->defaultValue('master')
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('tasks')
                                ->addDefaultsIfNotSet()
                                ->children()
                                    ->arrayNode('database_migration')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->booleanNode('enabled')
                                                ->defaultValue(false)
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('composer_update')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->booleanNode('enabled')
                                                ->defaultValue(false)
                                            ->end()
                                            ->scalarNode('bin')
                                                ->defaultValue('composer')
                                            ->end()
                                            ->scalarNode('memory_limit')
                                                ->defaultValue(null)
                                            ->end()
                                        ->end()
                                    ->end()
                                    ->arrayNode('assets_install')
                                        ->addDefaultsIfNotSet()
                                        ->children()
                                            ->booleanNode('enabled')
                                                ->defaultValue(false)
                                            ->end()
                                            ->booleanNode('symlink')
                                                ->defaultValue(true)
                                            ->end()
                                            ->booleanNode('relative')
                                                ->defaultValue(false)
                                            ->end()
                                            ->scalarNode('path')
                                                ->defaultValue('web')
                                            ->end()
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
