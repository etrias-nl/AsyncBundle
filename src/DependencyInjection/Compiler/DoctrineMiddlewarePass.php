<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\DependencyInjection\Compiler;

use Etrias\AsyncBundle\Middleware\TransactionMiddleware;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This compiler pass registers doctrine entity manager middleware.
 */
class DoctrineMiddlewarePass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if (!class_exists(TransactionMiddleware::class) || !$container->hasParameter('doctrine.entity_managers')) {
            return;
        }

        $entityManagers = $container->getParameter('doctrine.entity_managers');
        if (empty($entityManagers)) {
            return;
        }

        foreach ($entityManagers as $name => $serviceId) {
            $container->setDefinition(
                sprintf('etrias.async.doctrine.%s', $name),
                new Definition(TransactionMiddleware::class, [new Reference($serviceId)])
            );
        }

        $defaultEntityManager = $container->getParameter('doctrine.default_entity_manager');
        $container->setAlias('etrias.async.doctrine', sprintf('etrias.async.doctrine.%s', $defaultEntityManager));
    }
}
