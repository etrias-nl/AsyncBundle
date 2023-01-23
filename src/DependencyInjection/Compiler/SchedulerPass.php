<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\DependencyInjection\Compiler;

use Etrias\AsyncBundle\Check\GearmanCheck;
use Etrias\AsyncBundle\Console\Command\ExecuteCommand;
use Etrias\AsyncBundle\Handler\ScheduledCommandHandler;
use JMose\CommandSchedulerBundle\Command\ExecuteCommand as JMoseExecuteCommand;
use Dukecity\CommandSchedulerBundle\Command\ExecuteCommand as DukecityExecuteCommand;
use JMose\CommandSchedulerBundle\Command\StartSchedulerCommand as JMoseStartSchedulerCommand;
use Dukecity\CommandSchedulerBundle\Command\StartSchedulerCommand as DukecityStartSchedulerCommand;
use JMose\CommandSchedulerBundle\Command\StopSchedulerCommand as JMoseStopSchedulerCommand;
use Dukecity\CommandSchedulerBundle\Command\StopSchedulerCommand as DukecityStopSchedulerCommand;
use JMose\CommandSchedulerBundle\Command\UnlockCommand as JMoseUnlockCommand;
use Dukecity\CommandSchedulerBundle\Command\UnlockCommand as DukecityUnlockCommand;
use JMose\CommandSchedulerBundle\JMoseCommandSchedulerBundle;
use Dukecity\CommandSchedulerBundle\DukecityCommandSchedulerBundle;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Process\Process;

class SchedulerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (\in_array(JMoseCommandSchedulerBundle::class, $bundles, true) || \in_array(DukecityCommandSchedulerBundle::class, $bundles, true)) {
            if (!class_exists(Process::class)) {
                throw new \RuntimeException('The component "symfony/process" is required for async command handling');
            }

            if ($container->hasDefinition(JMoseExecuteCommand::class)) {
                $container->removeDefinition(JMoseExecuteCommand::class);
            }
            if ($container->hasDefinition(DukecityExecuteCommand::class)) {
                $container->removeDefinition(DukecityExecuteCommand::class);
            }

            if ($container->hasDefinition(JMoseStartSchedulerCommand::class)) {
                $container->removeDefinition(JMoseStartSchedulerCommand::class);
            }
            if ($container->hasDefinition(DukecityStartSchedulerCommand::class)) {
                $container->removeDefinition(DukecityStartSchedulerCommand::class);
            }

            if ($container->hasDefinition(JMoseStopSchedulerCommand::class)) {
                $container->removeDefinition(JMoseStopSchedulerCommand::class);
            }
            if ($container->hasDefinition(DukecityStopSchedulerCommand::class)) {
                $container->removeDefinition(DukecityStopSchedulerCommand::class);
            }

            if ($container->hasDefinition(JMoseUnlockCommand::class)) {
                $container->removeDefinition(JMoseUnlockCommand::class);
            }
            if ($container->hasDefinition(DukecityUnlockCommand::class)) {
                $container->removeDefinition(DukecityUnlockCommand::class);
            }
        } else {
            $container->removeDefinition(ExecuteCommand::class);
            $container->removeDefinition(JMoseStartSchedulerCommand::class);
            $container->removeDefinition(DukecityStartSchedulerCommand::class);
            $container->removeDefinition(ScheduledCommandHandler::class);
        }

        if (!$container->has('liip_monitor.runner')) {
            $container->removeDefinition(GearmanCheck::class);
        }
    }
}
