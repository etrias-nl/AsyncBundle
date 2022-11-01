<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\DependencyInjection;

use Etrias\AsyncBundle\Check\GearmanCheck;
use Etrias\AsyncBundle\Handler\ScheduledCommandHandler;
use Etrias\AsyncBundle\Middleware\AsyncMiddleware;
use Etrias\AsyncBundle\Module\JobConfig;
use Etrias\AsyncBundle\Registry\JobRegistry;
use Etrias\AsyncBundle\Registry\WorkerAnnotationRegistry;
use Etrias\AsyncBundle\Timer\NullTimer;
use Etrias\AsyncBundle\Timer\StopWatchTimer;
use Etrias\AsyncBundle\Timer\TimerInterface;
use Etrias\AsyncBundle\Workers\CommandBusWorker;
use Mmoreram\GearmanBundle\Driver\Gearman\Job as JobAnnotation;
use Mmoreram\GearmanBundle\Driver\Gearman\Work as WorkAnnotation;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Stopwatch\Stopwatch;

class EtriasAsyncExtension extends ConfigurableExtension
{
    /**
     * Configures the passed container according to the merged configuration.
     *
     * @throws \Exception
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
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

        $asyncMiddleware = $container->getDefinition(AsyncMiddleware::class);
        $asyncMiddleware->setArgument('$encoding', $mergedConfig['encoding']);
        $asyncMiddleware->setArgument('$workerEnvironment', $mergedConfig['worker_environment']);

        $profiling = $mergedConfig['profiling'] ?? $container->getParameter('kernel.debug');

        if (true === $profiling) {
            $asyncMiddleware->addMethodCall('setProfileLogger', [new Reference('etrias_async.profile_logger')]);
        } else {
            $container->removeDefinition('etrias_async.profile_logger');
            $container->removeDefinition('etrias_async.command_collector');
        }

        if (class_exists(Stopwatch::class)) {
            $container->setAlias(TimerInterface::class, StopWatchTimer::class);
            $container->removeDefinition(NullTimer::class);
        } else {
            $container->setAlias(TimerInterface::class, NullTimer::class);
            $container->removeDefinition(StopWatchTimer::class);

        }

        $this->processWorkerConfig($mergedConfig, $container);
        $this->processJobConfig($mergedConfig, $container);
        $this->processCheckConfig($mergedConfig, $container);
        $this->processScheduledCommand($mergedConfig, $container);
    }

    protected function processWorkerConfig(array $config, ContainerBuilder $container): void
    {
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
                    'service' => CommandBusWorker::class,
                ],
            ]);

            $checkConfig = [
                'minWorkers' => $worker['min_workers'],
                'maxJobs' => $worker['max_queued_jobs'],
            ];

            $workerRegistry->addMethodCall('add', [ucfirst($name), $workAnnotation, $checkConfig]);
        }
    }

    protected function processJobConfig(array $config, ContainerBuilder $container): void
    {
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
                ],
            ]);

            $jobConfig = new Definition(JobConfig::class, [
                $command['worker'],
                $jobAnnotation,
            ]);

            $checkConfig = [
                'maxJobs' => $command['max_queued_jobs'],
            ];

            $jobRegistry->addMethodCall('add', [$name, $jobConfig, $checkConfig]);
        }
    }

    protected function processCheckConfig(array $config, ContainerBuilder $container): void
    {
        if ($container->hasDefinition(GearmanCheck::class)) {
            $definition = $container->getDefinition(GearmanCheck::class);
            $definition->setArgument(0, $config['check']['min_workers']);
            $definition->setArgument(1, $config['check']['max_queued_jobs']);
        }
    }

    protected function processScheduledCommand(array $config, ContainerBuilder $container): void
    {
        $definition = $container->getDefinition(ScheduledCommandHandler::class);
        $definition->setArgument('$cwd', $config['scheduled_command']['cwd'] ?? $container->getParameter('kernel.project_dir'));
        $definition->setArgument('$consoleCommand', $config['scheduled_command']['console_command']);
    }
}
