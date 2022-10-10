<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Timer;

use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Stopwatch\StopwatchEvent;

class StopWatchTimer implements TimerInterface
{
    const LOG_NAME = 'commandbus_command';

    protected Stopwatch  $stopwatch;

    public function __construct()
    {
        $this->stopwatch = new Stopwatch();
        $this->stopwatch->start(self::LOG_NAME);

    }

    public function lap(): array
    {
        $event = $this->stopwatch->lap(self::LOG_NAME);

        return [
            'memory' => round($event->getMemory() / 1024 / 1024),
            'duration' => round($event->getDuration() / 1000),
            'iteration' => count($event->getPeriods()),
        ];
    }
}
