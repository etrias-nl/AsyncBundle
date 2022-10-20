<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\DependencyInjection\Compiler;

use Etrias\AsyncBundle\Check\GearmanCheck;
use Etrias\AsyncBundle\Console\Command\ExecuteCommand;
use Etrias\AsyncBundle\Handler\ScheduledCommandHandler;
use JMose\CommandSchedulerBundle\Command\ExecuteCommand as JMoseExecuteCommand;
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
            if ($container->hasDefinition(JMoseExecuteCommand::class)) {
                $container->removeDefinition(JMoseExecuteCommand::class);
            }

            if (!class_exists(Process::class)) {
                throw new \RuntimeException('The component "symfony/process" is required for async command handling');
            }

        } else {
            $container->removeDefinition(ExecuteCommand::class);
            $container->removeDefinition(ScheduledCommandHandler::class);
        }

        if (!$container->has('liip_monitor.runner')) {
            $container->removeDefinition(GearmanCheck::class);
        }
    }
}
