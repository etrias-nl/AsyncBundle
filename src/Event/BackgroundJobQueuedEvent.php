<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Event;


use Symfony\Component\EventDispatcher\Event;

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

    /**
     * @return string
     */
    public function getHandle(): string
    {
        return $this->handle;
    }

    /**
     * @return string
     */
    public function getJobMethod(): string
    {
        return $this->jobMethod;
    }
}
