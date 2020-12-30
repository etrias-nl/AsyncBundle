<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle;

use Etrias\AsyncBundle\DependencyInjection\Compiler\DoctrineMiddlewarePass;
use Etrias\AsyncBundle\DependencyInjection\Compiler\LiipMonitorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EtriasAsyncBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new LiipMonitorPass());
        $container->addCompilerPass(new DoctrineMiddlewarePass());
    }
}
