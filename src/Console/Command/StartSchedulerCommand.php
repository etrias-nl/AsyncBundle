<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Console\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Code originally taken from https://github.com/Cron/Symfony-Bundle/blob/2.1.0/Command/CronStartCommand.php
 * License: MIT (according to https://github.com/Cron/Symfony-Bundle/blob/2.1.0/LICENSE)
 * Original author: Alexander Lokhman <alex.lokhman@gmail.com>.
 *
 * Adaption to CommandSchedulerBundle by Christoph Singer <singer@webagentur72.de>
 */
class StartSchedulerCommand extends Command
{
    protected ?int $stopWorkSignalReceived = null;

    public function __construct()
    {
        /**
         * If the pcntl_signal exists, subscribe to the terminate and stop handling scheduled commands.
         */
        if(false !== function_exists('pcntl_signal'))
        {
            declare(ticks = 1);
            pcntl_signal(SIGTERM, [$this, "handleSystemSignal"]);
            pcntl_signal(SIGHUP,  [$this, "handleSystemSignal"]);

        }

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('scheduler:start')
            ->setDescription('Starts command scheduler');
    }

    public function handleSystemSignal($signo)
    {
        $this->stopWorkSignalReceived = $signo;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(sprintf('<info>%s</info>', 'Starting command scheduler.'));

        return $this->scheduler($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE ? $output : new NullOutput());

    }

    private function scheduler(OutputInterface $output)
    {
        $input = new ArrayInput([]);

        $console = $this->getApplication();
        $command = $console->find('scheduler:execute');

        while ($this->stopWorkSignalReceived === null) {
            $now = microtime(true);
            usleep((int) ((60 - ($now % 60) + (int) $now - $now) * 1e6));

            if (null !== $this->stopWorkSignalReceived) {
                return $this->stopWorkSignalReceived;
            }

            $command->run($input, $output);
        }

        return 0;
    }
}
