<?php

namespace Etrias\AsyncBundle\Module;

use Etrias\AsyncBundle\Exceptions\WorkerNotFoundException;
use Etrias\AsyncBundle\Module\WorkerClass as Worker;

/**
 * WorkerCollection class
 *
 * @since 2.3.1
 */
class WorkerCollection
{
    /**
     * All Workers
     *
     * @var Worker[]
     */
    protected $workerClasses = array();

    /**
     * Adds a Worker into $workerClasses
     * Return self object
     *
     * @param $name
     * @param Worker $workerClass Worker element to add
     *
     * @return WorkerCollection
     */
    public function add($name, Worker $workerClass)
    {
        $this->workerClasses[$name] = $workerClass;

        return $this;
    }

    /**
     * Retrieve all workers loaded previously in cache format
     *
     * @return array
     */
    public function toArray()
    {
        $workersDumped = array();

        foreach ($this->workerClasses as $worker) {
            $workersDumped[] = $worker->toArray();
        }

        return $workersDumped;
    }

    /**
     * @return Worker[]
     */
    public function getWorkers()
    {
        return $this->workerClasses;
    }

    public function getWorkerByName(string $workerName)
    {
        if (isset($this->workerClasses[$workerName])) {

            return $this->workerClasses[$workerName];
        }

        throw new WorkerNotFoundException(sprintf('Worker with name "%s" is not defined', $workerName));
    }
}
