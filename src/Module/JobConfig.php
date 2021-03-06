<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Module;

use Mmoreram\GearmanBundle\Driver\Gearman\Job as JobAnnotation;

class JobConfig
{
    /**
     * @var string
     */
    protected $workerName;
    /**
     * @var JobAnnotation
     */
    protected $annotation;

    public function __construct(string $workerName, JobAnnotation $annotation)
    {
        $this->workerName = $workerName;
        $this->annotation = $annotation;
    }

    public function getWorkerName(): string
    {
        return $this->workerName;
    }

    public function getAnnotation(): JobAnnotation
    {
        return $this->annotation;
    }
}
