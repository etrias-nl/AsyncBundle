<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\DataCollector;

use Etrias\AsyncBundle\Logger\ProfileLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\LateDataCollectorInterface;

class CommandCollector extends DataCollector implements LateDataCollectorInterface
{
    /**
     * @var ProfileLogger
     */
    private $logger;

    public function __construct(ProfileLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Collects data for the given Request and Response.
     */
    public function collect(Request $request, Response $response, \Exception $exception = null): void
    {
    }

    public function lateCollect(): void
    {
        $this->data['commands'] = $this->logger->getCommands();

        $this->data['time_spent'] = array_sum(array_map(function ($command) {
            return $command['executionMS'];
        }, $this->data['commands']));
    }

    /**
     * Returns the name of the collector.
     *
     * @return string The collector name
     */
    public function getName()
    {
        return 'etrias_async.command_collector';
    }

    public function reset(): void
    {
        $this->data['commands'] = [];
    }

    public function getCommands(): array
    {
        return $this->data['commands'] ?? [];
    }

    public function getGroupedCommands(): array
    {
        $commands = [];

        foreach ($this->getCommands() as $command) {
            $method = $command['method'];
            if (!isset($commands[$method])) {
                $commands[$method] = [];
            }

            $commands[$method][] = $command;
        }

        return $commands;
    }

    public function getTimeSpent()
    {
        return $this->data['time_spent'] ?? 0;
    }
}
