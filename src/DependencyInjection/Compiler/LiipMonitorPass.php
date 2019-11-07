<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\DependencyInjection\Compiler;

use Etrias\AsyncBundle\Check\GearmanCheck;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class LiipMonitorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('liip_monitor.runner')) {
            $container->removeDefinition(GearmanCheck::class);
        }
    }
}
