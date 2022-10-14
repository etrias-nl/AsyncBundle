<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Middleware;

use Ekino\NewRelicBundle\NewRelic\Config;
use Ekino\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use League\Tactician\Middleware;
use Symfony\Component\HttpKernel\DependencyInjection\ServicesResetter;

class ResetServicesMiddleware implements Middleware
{
    private bool $running = false;
    protected ServicesResetter $servicesResetter;

    public function __construct(ServicesResetter $servicesResetter)
    {
        $this->servicesResetter = $servicesResetter;
    }

    public function execute($command, callable $next)
    {
        if ($this->running) {
            return $next($command);
        }

        $this->running = true;

        try {
            return $next($command);
        } finally {
            $this->servicesResetter->reset();
            $this->running = false;
        }
    }
}
