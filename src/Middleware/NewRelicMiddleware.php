<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Middleware;

use Ekino\NewRelicBundle\NewRelic\Config;
use Ekino\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use League\Tactician\Middleware;

class NewRelicMiddleware implements Middleware
{
    protected Config $config;
    protected NewRelicInteractorInterface $interactor;
    protected bool $running = false;

    public function __construct(Config $config, NewRelicInteractorInterface $interactor)
    {
        $this->config = $config;
        $this->interactor = $interactor;
    }

    public function execute($command, callable $next)
    {
        if ($this->running) {
            return $next($command);
        }

        if ($this->config->getName()) {
            $this->interactor->setApplicationName($this->config->getName(), $this->config->getLicenseKey(), $this->config->getXmit());
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
