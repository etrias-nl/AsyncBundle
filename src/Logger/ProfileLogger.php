<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Logger;

class ProfileLogger
{
    /**
     * @var array
     */
    protected $commands = [];

    /**
     * float.
     */
    protected $start;

    /**
     * @var int
     */
    protected $currentCommand = 0;

    public function startCommand($command, string $method, $jobConfig): void
    {
        $this->start = microtime(true);
        $this->commands[++$this->currentCommand] = [
            'command' => $command,
            'method' => $method,
            'jobConfig' => $jobConfig,
            'executionMS' => 0,
        ];
    }

    public function stopCommand(): void
    {
        $this->commands[$this->currentCommand]['executionMS'] = microtime(true) - $this->start;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }
}
