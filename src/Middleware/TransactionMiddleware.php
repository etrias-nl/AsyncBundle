<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use League\Tactician\Middleware;
use Throwable;

/**
 * Wraps command execution inside a Doctrine ORM transaction.
 */
class TransactionMiddleware implements Middleware
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var bool
     */
    private $isExecuting = false;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Executes the given command and optionally returns a value.
     *
     * @param object $command
     *
     * @throws Throwable
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        if ($this->isExecuting) {
            return $next($command);
        }
        $this->isExecuting = true;
        $this->entityManager->beginTransaction();

        try {
            $returnValue = $next($command);

            $this->entityManager->flush();
            if (!$this->entityManager->getConnection()->isRollbackOnly()) {
                $this->entityManager->commit();
            }
        } catch (Throwable $e) {
            try {
                $this->rollbackTransaction();
            } catch (\Exception) {
            }

            throw $e;
        } finally {
            $this->isExecuting = false;
        }

        return $returnValue;
    }

    /**
     * Rollback the current transaction and close the entity manager when possible.
     */
    protected function rollbackTransaction(): void
    {
        $this->entityManager->rollback();

        $connection = $this->entityManager->getConnection();
        if (!$connection->isTransactionActive() || $connection->isRollbackOnly()) {
            $this->entityManager->close();
        }
    }
}
