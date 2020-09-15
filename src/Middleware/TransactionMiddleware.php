<?php
namespace Etrias\AsyncBundle\Middleware;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use League\Tactician\Middleware;
use Exception;
use Throwable;

/**
 * Wraps command execution inside a Doctrine ORM transaction
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

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Executes the given command and optionally returns a value
     *
     * @param object $command
     * @param callable $next
     * @return mixed
     * @throws Throwable
     * @throws Exception
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
            $this->entityManager->commit();

            // @fixme; define event class, move out async-bundle (e.g. doctrine-utils)
            $this->entityManager->getEventManager()->dispatchEvent('postCommit', new PostFlushEventArgs($this->entityManager));

            $this->entityManager->clear();
        } catch (Exception $e) {
            $this->rollbackTransaction();

            throw $e;
        } catch (Throwable $e) {
            $this->rollbackTransaction();

            throw $e;
        }

        $this->isExecuting = false;
        return $returnValue;
    }

    /**
     * Rollback the current transaction and close the entity manager when possible.
     */
    protected function rollbackTransaction()
    {
        $this->entityManager->rollback();

        $connection = $this->entityManager->getConnection();
        if (!$connection->isTransactionActive() || $connection->isRollbackOnly()) {
            $this->entityManager->close();
        }
    }
}
