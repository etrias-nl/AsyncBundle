<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Module;

use Doctrine\Common\Annotations\Reader;
use Mmoreram\GearmanBundle\Driver\Gearman\Work as WorkAnnotation;
use Mmoreram\GearmanBundle\Module\JobCollection;
use Mmoreram\GearmanBundle\Module\WorkerClass as MmoreramWorkerClass;
use ReflectionClass;

class WorkerClass extends MmoreramWorkerClass
{
    /**
     * @var JobCollection
     *
     * All jobs inside Worker
     */
    protected $jobCollection;

    public function __construct(
        WorkAnnotation $workAnnotation,
        ReflectionClass $reflectionClass,
        Reader $reader,
        array $servers,
        array $defaultSettings
    ) {
        parent::__construct($workAnnotation, $reflectionClass, $reader, $servers, $defaultSettings);

        $this->jobCollection = new JobCollection();
    }

    public function getJobCollection(): JobCollection
    {
        return $this->jobCollection;
    }

    public function toArray(): array
    {
        $array = parent::toArray();
        $array['jobs'] = $this->jobCollection->toArray();

        return $array;
    }
}
