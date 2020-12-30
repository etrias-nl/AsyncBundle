<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const GEARMAN_METHODS = [
        'doNormal',
        'doBackground',
        'doHigh',
        'doHighBackground',
        'doLow',
        'doLowBackground',
    ];

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('etrias_async');

        // Keep compatibility with symfony/config < 4.2
        if (!method_exists($treeBuilder, 'getRootNode')) {
            $rootNode = $treeBuilder->root('etrias_async');
        } else {
            $rootNode = $treeBuilder->getRootNode();
        }

        $rootNode
            ->isRequired()
            ->children()
                ->booleanNode('profiling')
                    ->defaultNull()
                ->end()
                ->scalarNode('worker_environment')
                    ->defaultValue('worker')
                ->end()
                ->scalarNode('encoding')
                    ->isRequired()
                ->end()
                ->scalarNode('default_worker')
                    ->isRequired()
                ->end()
                ->arrayNode('check')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('min_workers')
                            ->defaultValue(1)
                        ->end()
                        ->integerNode('max_queued_jobs')
                            ->defaultValue(100000)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('workers')
                    ->useAttributeAsKey('name')
                    ->requiresAtLeastOneElement()
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('description')
                                ->defaultNull()
                            ->end()
                            ->integerNode('iterations')
                                ->min(0)
                                ->defaultValue(0)
                            ->end()
                            ->integerNode('minimum_execution_time')
                                ->min(0)
                                ->defaultNull()
                            ->end()
                            ->integerNode('timeout')
                                ->min(0)
                                ->defaultNull()
                            ->end()
                            ->integerNode('min_workers')
                                ->min(0)
                                ->defaultNull()
                            ->end()
                            ->integerNode('max_queued_jobs')
                                ->min(0)
                                ->defaultNull()
                            ->end()
                            ->enumNode('gearman_method')
                                ->values(static::GEARMAN_METHODS)
                                ->defaultValue('doNormal')
                            ->end()
                            ->arrayNode('servers')
                                ->performNoDeepMerging()
                                ->defaultValue([
                                    'localhost' => [
                                        'host' => '127.0.0.1',
                                        'port' => '4730',
                                    ],
                                ])
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('host')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->integerNode('port')
                                            ->defaultValue('4730')
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('commands')
                    ->requiresAtLeastOneElement()
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('worker')
                                ->isRequired()
                                ->beforeNormalization()
                                    ->always(function ($worker) {
                                        return ucfirst($worker);
                                    })
                                    ->end()
                            ->end()
                            ->scalarNode('description')
                                ->defaultNull()
                            ->end()
                            ->integerNode('iterations')
                                ->min(0)
                                ->defaultValue(0)
                            ->end()
                            ->integerNode('minimum_execution_time')
                                ->min(0)
                                ->defaultNull()
                            ->end()
                            ->integerNode('timeout')
                                ->min(0)
                                ->defaultNull()
                            ->end()
                            ->integerNode('max_queued_jobs')
                                ->min(0)
                                ->defaultNull()
                            ->end()
                            ->enumNode('gearman_method')
                                ->values(static::GEARMAN_METHODS)
                                ->defaultValue('doNormal')
                            ->end()
                            ->arrayNode('servers')
                                ->performNoDeepMerging()
                                ->defaultValue([
                                    'localhost' => [
                                        'host' => '127.0.0.1',
                                        'port' => '4730',
                                    ],
                                ])
                                ->prototype('array')
                                    ->children()
                                        ->scalarNode('host')
                                            ->isRequired()
                                            ->cannotBeEmpty()
                                        ->end()
                                        ->integerNode('port')
                                            ->defaultValue('4730')
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
