<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Service;

use Mmoreram\GearmanBundle\Service\GearmanDescriber as MmereramGearmanDescriber;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class GearmanDescriber extends MmereramGearmanDescriber
{
    /**
     * @var string
     */
    protected $workerEnvironment;
    /**
     * @var KernelInterface
     */
    protected $kernel;

    public function __construct(KernelInterface $kernel, string $workerEnvironment)
    {
        parent::__construct($kernel);
        $this->workerEnvironment = $workerEnvironment;
        $this->kernel = $kernel;
    }

    /**
     * Describe Job.
     *
     * Given a output object and a Job, dscribe it.
     *
     * @param OutputInterface $output Output object
     * @param array           $worker Worker array with Job to describe
     */
    public function describeJob(OutputInterface $output, array $worker): void
    {
        /**
         * Commandline.
         */
        $script = $this->kernel->getRootDir().'/console gearman:job:execute';

        /*
         * A job descriptions contains its worker description
         */
        $this->describeWorker($output, $worker);

        $job = $worker['job'];
        $output->writeln('<info>@job\methodName : '.$job['methodName'].'</info>');
        $output->writeln('<info>@job\callableName : '.$job['realCallableName'].'</info>');

        if ($job['jobPrefix']) {
            $output->writeln('<info>@job\jobPrefix : '.$job['jobPrefix'].'</info>');
        }

        /*
         * Also a complete and clean execution path is given , for supervisord
         */
        $output->writeln('<info>@job\supervisord : </info><comment>/usr/bin/php '.$script.' '.$job['realCallableName'].' --no-interaction --env='.$this->workerEnvironment.'</comment>');
        $output->writeln('<info>@job\iterations : '.$job['iterations'].'</info>');
        $output->writeln('<info>@job\defaultMethod : '.$job['defaultMethod'].'</info>');

        /*
         * Printed every server is defined for current job
         */
        $output->writeln('');
        $output->writeln('<info>@job\servers :</info>');
        $output->writeln('');
        foreach ($job['servers'] as $name => $server) {
            $output->writeln('<comment>    '.$name.' - '.$server['host'].':'.$server['port'].'</comment>');
        }

        /*
         * Description
         */
        $output->writeln('');
        $output->writeln('<info>@job\description :</info>');
        $output->writeln('');
        $output->writeln('<comment>    #'.$job['description'].'</comment>');
        $output->writeln('');
    }
}
