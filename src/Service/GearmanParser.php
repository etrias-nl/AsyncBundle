<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Service;

use Doctrine\Common\Annotations\Reader;
use Etrias\AsyncBundle\Module\JobClass;
use Etrias\AsyncBundle\Module\WorkerClass;
use Etrias\AsyncBundle\Module\WorkerCollection;
use Etrias\AsyncBundle\Registry\JobRegistry;
use Etrias\AsyncBundle\Registry\WorkerAnnotationRegistry;
use Etrias\AsyncBundle\Workers\CommandBusWorker;
use Mmoreram\GearmanBundle\Service\GearmanParser as MmoreramGearmanParser;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\KernelInterface;

class GearmanParser extends MmoreramGearmanParser
{
    /**
     * @var KernelInterface
     *
     * Kernel object
     */
    protected $kernel;

    /**
     * {@inheritdoc}
     */
    protected $servers;

    /**
     * {@inheritdoc}
     */
    protected $defaultSettings;
    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @var WorkerAnnotationRegistry
     */
    protected $workerAnnotationRegistry;

    /**
     * @var JobRegistry
     */
    protected $jobRegistry;

    public function __construct(
        KernelInterface $kernel,
        Reader $reader,
        Finder $finder,
        array $bundles,
        array $resources,
        array $servers,
        array $defaultSettings
    ) {
        parent::__construct($kernel, $reader, $finder, $bundles, $resources, $servers, $defaultSettings);
        $this->servers = $servers;
        $this->kernel = $kernel;

        $this->defaultSettings = $this->normalizeDefaultSettings($defaultSettings);
        $this->reader = $reader;
        $this->workerAnnotationRegistry = $kernel->getContainer()->get(WorkerAnnotationRegistry::class);
        $this->jobRegistry = $kernel->getContainer()->get(JobRegistry::class);
    }

    public function parseNamespaceMap(
        Finder $finder,
        Reader $reader,
        array $paths,
        array $excludedPaths
    ) {
        //* Do not call parent, because code is not extendable nor injectable :(

        $workerCollection = new WorkerCollection();

        foreach ($this->workerAnnotationRegistry->getAllAnnotations() as $name => $annotation) {
            $reflectionClass = new ReflectionClass(CommandBusWorker::class);

            $this->defaultSettings['workers_name_prepend_namespace'] = false;
            $worker = new WorkerClass($annotation, $reflectionClass, $reader, $this->servers, $this->defaultSettings);
            $workerCollection->add($name, $worker);
        }

        foreach ($this->jobRegistry->getAllConfigs() as $config) {
            $worker = $workerCollection->getWorkerByName($config->getWorkerName());
            $workerProperties = $worker->toArray();
            $jobCollection = $worker->getJobCollection();

            $reflectionMethod = new ReflectionMethod(CommandBusWorker::class, 'handle');

            $job = new JobClass($config->getAnnotation(), $reflectionMethod, $workerProperties['callableName'], $this->servers, $this->defaultSettings);

            $jobCollection->add($job);
        }

        return $workerCollection;
    }

    /**
     * @return array
     */
    protected function normalizeDefaultSettings(array $defaultSettings)
    {
        $defaultSettings['jobPrefix'] = $defaultSettings['job_prefix'];

        return $defaultSettings;
    }
}
