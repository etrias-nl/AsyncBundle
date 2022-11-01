<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\DependencyInjection\Compiler;

use Etrias\AsyncBundle\Check\GearmanCheck;
use Etrias\AsyncBundle\Console\Command\ExecuteCommand;
use Etrias\AsyncBundle\Handler\ScheduledCommandHandler;
use JMose\CommandSchedulerBundle\Command\ExecuteCommand as JMoseExecuteCommand;
use JMose\CommandSchedulerBundle\Command\StartSchedulerCommand;
use JMose\CommandSchedulerBundle\Command\StartSchedulerCommand as JMoseStartSchedulerCommand;
use JMose\CommandSchedulerBundle\Command\StopSchedulerCommand as JMoseStopSchedulerCommand;
use JMose\CommandSchedulerBundle\Command\UnlockCommand as JMoseUnlockCommand;
use JMose\CommandSchedulerBundle\JMoseCommandSchedulerBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Process\Process;

class SchedulerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (\in_array(JMoseCommandSchedulerBundle::class, $bundles, true)) {
            if (!class_exists(Process::class)) {
                throw new \RuntimeException('The component "symfony/process" is required for async command handling');
            }

            if ($container->hasDefinition(JMoseExecuteCommand::class)) {
                $container->removeDefinition(JMoseExecuteCommand::class);
            }

            if ($container->hasDefinition(JMoseStartSchedulerCommand::class)) {
                $container->removeDefinition(JMoseStartSchedulerCommand::class);
            }

            if ($container->hasDefinition(JMoseStopSchedulerCommand::class)) {
                $container->removeDefinition(JMoseStopSchedulerCommand::class);
            }

            if ($container->hasDefinition(JMoseUnlockCommand::class)) {
                $container->removeDefinition(JMoseUnlockCommand::class);
            }
        } else {
            $container->removeDefinition(ExecuteCommand::class);
            $container->removeDefinition(StartSchedulerCommand::class);
            $container->removeDefinition(ScheduledCommandHandler::class);
        }

        if (!$container->has('liip_monitor.runner')) {
            $container->removeDefinition(GearmanCheck::class);
        }
    }
}
