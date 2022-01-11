<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Check;

use Etrias\AsyncBundle\Registry\JobRegistry;
use Etrias\AsyncBundle\Registry\WorkerAnnotationRegistry;
use Etrias\AsyncBundle\Service\GearmanManager;
use Mmoreram\GearmanBundle\Service\GearmanClient;
use Laminas\Diagnostics\Check\AbstractCheck;
use Laminas\Diagnostics\Result\Failure;
use Laminas\Diagnostics\Result\ResultInterface;
use Laminas\Diagnostics\Result\Success;

class GearmanCheck extends AbstractCheck
{
    /**
     * @var GearmanManager
     */
    protected $gearmanManager;

    /**
     * @var int
     */
    protected $minWorkers;

    /**
     * @var int
     */
    protected $maxJobs;

    /**
     * @var JobRegistry
     */
    protected $jobRegistry;

    /**
     * @var GearmanClient
     */
    protected $gearmanClient;

    /**
     * @var WorkerAnnotationRegistry
     */
    protected $workerRegistry;

    public function __construct(
        int $minWorkers,
        int $maxJobs,
        GearmanManager $gearmanManager,
        JobRegistry $jobRegistry,
        GearmanClient $gearmanClient,
        WorkerAnnotationRegistry $workerRegistry
    ) {
        $this->minWorkers = $minWorkers;
        $this->maxJobs = $maxJobs;
        $this->gearmanManager = $gearmanManager;
        $this->jobRegistry = $jobRegistry;
        $this->gearmanClient = $gearmanClient;
        $this->workerRegistry = $workerRegistry;
    }

    /**
     * Perform the actual check and return a ResultInterface.
     *
     * @return ResultInterface
     */
    public function check()
    {
        try {
            $overloadedJobs = [];
            $missingWorkers = [];

            $workers = $this->gearmanClient->getWorkers();

            $statusData = [];
            foreach ($this->gearmanManager->getStatus() as $server => $jobs) {
                $statusData += $jobs;
            }

            foreach ($workers as $worker) {
                $minWorkers = $this->minWorkers;
                $maxJobs = $this->maxJobs;

                $workerName = $worker['callableName'];
                if ($this->workerRegistry->hasCheckConfig($workerName)) {
                    $workerConfig = $this->workerRegistry->getCheckConfig($workerName);
                    if (!empty($workerConfig['minWorkers'])) {
                        $minWorkers = $workerConfig['minWorkers'];
                    }
                    if (!empty($workerConfig['maxJobs'])) {
                        $maxJobs = $workerConfig['maxJobs'];
                    }
                }

                foreach ($worker['jobs'] as $job) {
                    $jobMaxJobs = $maxJobs;
                    $jobName = $job['callableName'];
                    if ($this->jobRegistry->hasCheckConfig($jobName)) {
                        $jobConfig = $this->jobRegistry->getCheckConfig($jobName);
                        if (!empty($jobConfig['maxJobs'])) {
                            $jobMaxJobs = $jobConfig['maxJobs'];
                        }
                    }

                    $status = $statusData[$job['realCallableName']] ?? [];

                    if (empty($status) || $status['capable_workers'] < $minWorkers) {
                        $missingWorkers[] = $jobName;
                    } elseif ($status['in_queue'] > $jobMaxJobs) {
                        $overloadedJobs[] = $jobName;
                    }
                }
            }

            if (\count($overloadedJobs) || \count($missingWorkers)) {
                return new Failure(
                    sprintf(
                        'Invalid Gearman Behaviour. Overloaded: %s. Missing Workers: %s',
                        $overloadedJobs ? implode(',', $overloadedJobs) : 'None',
                        $missingWorkers ? implode(',', $missingWorkers) : 'None'
                    )
                );
            }
        } catch (\Net_Gearman_Exception $e) {
            return new Failure('Gearman Server is not responding');
        }

        return new Success('Gearman is working');
    }
}
