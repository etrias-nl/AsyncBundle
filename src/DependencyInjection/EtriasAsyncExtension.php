<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\DependencyInjection;

use Etrias\AsyncBundle\Check\GearmanCheck;
use Etrias\AsyncBundle\Middleware\AsyncMiddleware;
use Etrias\AsyncBundle\Module\JobConfig;
use Etrias\AsyncBundle\Registry\JobRegistry;
use Etrias\AsyncBundle\Registry\WorkerAnnotationRegistry;
use Etrias\AsyncBundle\Workers\CommandBusWorker;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Mmoreram\GearmanBundle\Driver\Gearman\Work as WorkAnnotation;
use Mmoreram\GearmanBundle\Driver\Gearman\Job as JobAnnotation;

class EtriasAsyncExtension extends ConfigurableExtension
{
    /**
     * Configures the passed container according to the merged configuration.
     * @param array $mergedConfig
     * @param ContainerBuilder $container
     * @throws \Exception
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container)
    {
        $xmlFileLoader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $ymlFileLoader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        $xmlFileLoader->load('services.xml');
        $ymlFileLoader->load('parameters.yml');

        $cacheMiddleware = $container->getDefinition(AsyncMiddleware::class);
        $cacheMiddleware->setArgument('$encoding', $mergedConfig['encoding']);
        $cacheMiddleware->setArgument('$workerEnvironment', $mergedConfig['worker_environment']);

        $profiling = $mergedConfig['profiling'] ?? $container->getParameter('kernel.debug');

        if ($profiling === true ) {
            $cacheMiddleware->addMethodCall('setProfileLogger', [new Reference('etrias_async.profile_logger')]);
        } else {
            $container->removeDefinition('etrias_async.profile_logger');
            $container->removeDefinition('etrias_async.command_collector');
        }

        $this->processWorkerConfig($mergedConfig, $container);
        $this->processJobConfig($mergedConfig, $container);
        $this->processCheckConfig($mergedConfig, $container);


    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function processWorkerConfig(array $config, ContainerBuilder $container) {
        $worker = $container->getDefinition(CommandBusWorker::class);
        $worker->setArgument(2, $config['encoding']);

        $workerRegistry = $container->getDefinition(WorkerAnnotationRegistry::class);

        if ($container->hasDefinition('gearman.describer')) {
            $gearmanDescriber = $container->getDefinition('gearman.describer');
            $gearmanDescriber->setArgument(1, $config['worker_environment']);
        }


        foreach ($config['workers'] as $name => $worker) {
            $workAnnotation = new Definition(WorkAnnotation::class, [
                [
                    'name' => ucfirst($name),
                    'description' => $worker['description'],
                    'iterations' => $worker['iterations'],
                    'minimumExecutionTime' => $worker['minimum_execution_time'],
                    'timeout' => $worker['timeout'],
                    'defaultMethod' => $worker['gearman_method'],
                    'service' => CommandBusWorker::class
                ]
            ]);

            $checkConfig = [
                'minWorkers' => $worker['min_workers'],
                'maxJobs' => $worker['max_queued_jobs'],
            ];

            $workerRegistry->addMethodCall('add', [ucfirst($name), $workAnnotation, $checkConfig]);
        }
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function processJobConfig(array $config, ContainerBuilder $container) {
        $jobRegistry = $container->getDefinition(JobRegistry::class);
        foreach ($config['commands'] as $name => $command) {
            $jobAnnotation = new Definition(JobAnnotation::class, [
                [
                    'name' => $name,
                    'description' => $command['description'],
                    'iterations' => $command['iterations'],
                    'minimumExecutionTime' => $command['minimum_execution_time'],
                    'timeout' => $command['timeout'],
                    'defaultMethod' => $command['gearman_method'],
                ]
            ]);

            $jobConfig = new Definition(JobConfig::class, [
                $command['worker'],
                $jobAnnotation
            ]);

            $checkConfig = [
                'maxJobs' => $command['max_queued_jobs'],
            ];

            $jobRegistry->addMethodCall('add', [$name, $jobConfig, $checkConfig]);
        }
    }

    /**
     * @param array $config
     * @param ContainerBuilder $container
     */
    protected function processCheckConfig(array $config, ContainerBuilder $container) {
        if ($container->hasDefinition(GearmanCheck::class)) {
            $definition = $container->getDefinition(GearmanCheck::class);
            $definition->setArgument(0, $config['check']['min_workers']);
            $definition->setArgument(1, $config['check']['max_queued_jobs']);
        }
    }
}
