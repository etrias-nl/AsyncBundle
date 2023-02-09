<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Handler;

use Cron\CronExpression;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Etrias\AsyncBundle\Command\ScheduledCommandCommand;
use Etrias\CqrsBundle\Handlers\HandlerInterface;
use JMose\CommandSchedulerBundle\Entity\Repository\ScheduledCommandRepository;
use JMose\CommandSchedulerBundle\Entity\ScheduledCommand;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class ScheduledCommandHandler implements HandlerInterface
{
    protected EntityManager $entityManager;
    protected ScheduledCommandRepository $commandRepository;
    protected LoggerInterface $logger;
    protected string $cwd;
    protected bool $debug;
    protected string $consoleCommand;

    public function __construct(
        ManagerRegistry $registry,
        LoggerInterface $cronLogger,
        string $cwd,
        bool $debug,
        string $consoleCommand
    )
    {
        $this->entityManager = $registry->getManagerForClass(ScheduledCommand::class);
        $this->commandRepository = $this->entityManager->getRepository(ScheduledCommand::class);
        $this->logger = $cronLogger;

        $this->cwd = $cwd;
        $this->debug = $debug;
        $this->consoleCommand = $consoleCommand;
    }

    public function handle(ScheduledCommandCommand $scheduledCommandCommand)
    {
        $scheduledCommand = $this->commandRepository->find($scheduledCommandCommand->getCommandId());

        if (!$scheduledCommand) {
            $this->logger->info(
                'Command not found in database',
                ['command' => $scheduledCommandCommand->getCommandId()]
            );
            return;
        }

        if ($scheduledCommand->isDisabled()) {
            $this->logger->info(
                'Command is disabled',
                ['command' => $scheduledCommand->getCommand(), 'args' => $scheduledCommand->getArguments()]
            );
            return;
        }

        $cron = CronExpression::factory($scheduledCommand->getCronExpression());
        $nextRunDate = $cron->getNextRunDate($scheduledCommand->getLastExecution());
        $now = new \DateTime();

        if ($scheduledCommand->isExecuteImmediately()) {
            $this->logger->info(
                'Immediate execution requested',
                ['command' => $scheduledCommand->getCommand(), 'args' => $scheduledCommand->getArguments()]
            );

            $this->executeCommand($scheduledCommand);

            return;
        } elseif ($nextRunDate < $now) {
            $this->logger->info('Command should be executed',
                [
                    'command' => $scheduledCommand->getCommand(),
                    'args' => $scheduledCommand->getArguments(),
                    'last_run' => $scheduledCommand->getLastExecution()->format(\DateTimeInterface::ATOM)
                ]
            );
            $this->executeCommand($scheduledCommand);

            return;
        }

        $this->logger->info('No need to execute command',
            [
                'command' => $scheduledCommand->getCommand(),
                'args' => $scheduledCommand->getArguments(),
                'last_run' => $scheduledCommand->getLastExecution()->format(\DateTimeInterface::ATOM)
            ]
        );
    }

    protected function executeCommand(ScheduledCommand $scheduledCommand)
    {
        $this->logger->info(
            'Start executing command',
            ['command' => $scheduledCommand->getCommand(), 'args' => $scheduledCommand->getArguments()]
        );

        $args = [
            $this->consoleCommand,
            $scheduledCommand->getCommand(),
            '--no-interaction',
            '--no-ansi',
            '--env',
            $this->debug ? 'dev' : 'prod'
        ];

        if ($scheduledCommand->getArguments()) {
            $args = array_merge(
                $args,
                explode(' ', $scheduledCommand->getArguments())
            );
        }

        $process = new Process(
            $args,
            $this->cwd,
            null,
            null,
            null
        );

        try {
            $process->mustRun();

            $this->logger->info(
                'Finished executing command',
                ['command' => $scheduledCommand->getCommand(), 'args' => $scheduledCommand->getArguments()]
            );
        } catch (ProcessFailedException $e) {
            $this->logger->error(
                $e->getMessage(),
                ['command' => $scheduledCommand->getCommand(), 'args' => $scheduledCommand->getArguments()]
            );
        }

        $scheduledCommand->setLastReturnCode($process->getExitCode());
        $scheduledCommand->setLastExecution(new \DateTime());

        $this->entityManager->flush();
    }
}
