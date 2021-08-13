<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Middleware;

use Ekino\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use League\Tactician\Middleware;

class NewRelicMiddleware implements Middleware
{
    protected NewRelicInteractorInterface $interactor;

    public function __construct(NewRelicInteractorInterface $interactor)
    {
        $this->interactor = $interactor;
    }

    public function execute($command, callable $next)
    {
        $this->interactor->startTransaction(\get_class($command));

        try {
            return $next($command);
        } finally {
            $this->interactor->endTransaction();
        }
    }
}
