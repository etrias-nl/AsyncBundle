<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Event;


use Symfony\Component\EventDispatcher\Event;

class BackgroundJobHandledEvent extends Event
{
    const NAME = 'etrias_async.background_job_handled';

    /**
     * @var mixed
     */
    protected $result;

    /**
     * @var string
     */
    protected $handle;
    /**
     * @var array
     */
    protected $workload;


    /**
     * BackgroundJobHandledEvent constructor.
     * @param string $handle
     * @param array $workload
     * @param $result
     */
    public function __construct(string $handle, array $workload, $result)
    {

        $this->result = $result;
        $this->handle = $handle;
        $this->workload = $workload;
    }

    /**
     * @return mixed
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @return string
     */
    public function getHandle(): string
    {
        return $this->handle;
    }

    /**
     * @return array
     */
    public function getWorkload(): array
    {
        return $this->workload;
    }


}
