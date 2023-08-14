<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Console\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\SignalableCommandInterface;
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
class StartSchedulerCommand extends Command implements SignalableCommandInterface
{
    protected ?int $stopWorkSignalReceived = null;

    public function getSubscribedSignals(): array
    {
        return [SIGTERM, SIGHUP];
    }

    public function handleSignal(int $signal): void
    {
        $this->stopWorkSignalReceived = $signal;
    }

    protected function configure(): void
    {
        $this->setName('scheduler:start')
            ->setDescription('Starts command scheduler');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(sprintf('<info>%s</info>', 'Starting command scheduler.'));

        return $this->scheduler($output->getVerbosity() >= OutputInterface::VERBOSITY_VERBOSE ? $output : new NullOutput());
    }

    private function scheduler(OutputInterface $output): int
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
