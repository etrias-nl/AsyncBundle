<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Registry;


use Etrias\AsyncBundle\Exceptions\WorkerNotFoundException;
use Mmoreram\GearmanBundle\Driver\Gearman\Work as WorkAnnotation;

class WorkerAnnotationRegistry
{

    /**
     * @var iterable|WorkAnnotation[]
     */
    protected $annotations;

    /**
     * @var iterable|[]
     */
    protected $checkConfigs;

    public function __construct(
        iterable $annotations = [],
        iterable $checkConfigs = []
    )
    {
        $this->annotations = $annotations;
        $this->checkConfigs = $checkConfigs;
    }

    /**
     * @return WorkAnnotation[]|iterable
     */
    public function getAllAnnotations() {
        return $this->annotations;
    }

    /**
     * @param string $name
     * @return WorkAnnotation
     * @throws WorkerNotFoundException
     */
    public function getByName(string $name) {
        if (isset($this->annotations[$name])) {
            return $this->annotations[$name];
        }

        throw new WorkerNotFoundException(sprintf('Worker with name "%s" not found', $name));
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasCheckConfig(string $name): bool{
        return isset($this->checkConfigs[$name]);
    }

    /**
     * @param string $name
     * @return array
     */
    public function getCheckConfig(string $name): array {
        return $this->checkConfigs[$name];
    }

    /**
     * @param string $name
     * @param WorkAnnotation $annotation
     * @return $this
     */
    public function add(string $name, workAnnotation $annotation, array $workerConfig) {
        $this->annotations[$name] = $annotation;
        $this->checkConfigs[$name] = $workerConfig;

        return $this;
    }
}
