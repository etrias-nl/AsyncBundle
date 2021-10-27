<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BackgroundJobQueuedEvent extends Event
{
    const NAME = 'etrias_async.background_job_queued';

    /**
     * @var mixed
     */
    protected $command;

    /**
     * @var string
     */
    protected $handle;

    /**
     * @var string
     */
    protected $jobMethod;

    public function __construct($command, string $jobMethod, string $handle)
    {
        $this->command = $command;
        $this->handle = $handle;
        $this->jobMethod = $jobMethod;
    }

    /**
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }

    public function getHandle(): string
    {
        return $this->handle;
    }

    public function getJobMethod(): string
    {
        return $this->jobMethod;
    }
}
