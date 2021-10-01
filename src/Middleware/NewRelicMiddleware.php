<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Middleware;

use Ekino\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use League\Tactician\Middleware;

class NewRelicMiddleware implements Middleware
{
    protected NewRelicInteractorInterface $interactor;
    protected bool $running = false;

    public function __construct(NewRelicInteractorInterface $interactor)
    {
        $this->interactor = $interactor;
    }

    public function execute($command, callable $next)
    {
        if ($this->running) {
            return $next($command);
        }

        $this->interactor->startTransaction(\get_class($command));
        $this->running = true;

        try {
            return $next($command);
        } finally {
            $this->interactor->endTransaction();
            $this->running = false;
        }
    }
}
