<?php

declare(strict_types=1);

/*
 * This file is part of PHP CS Fixer.
 */

namespace Etrias\AsyncBundle;

use Etrias\AsyncBundle\DependencyInjection\Compiler\DoctrineMiddlewarePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EtriasAsyncBundle extends Bundle
{

    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DoctrineMiddlewarePass());
    }
}
