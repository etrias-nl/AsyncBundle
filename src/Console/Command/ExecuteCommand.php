<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Console\Command;

use Cron\CronExpression;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Etrias\AsyncBundle\Command\ScheduledCommandCommand;
use Etrias\AsyncBundle\Logger\ScheduledCommandProcessor;
use JMose\CommandSchedulerBundle\Entity\ScheduledCommand as JMoseScheduledCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand as DukecityScheduledCommand;
use League\Tactician\CommandBus;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Class ExecuteCommand : This class is the entry point to execute all scheduled command.
 *
 * @author  Julien Guyon <julienguyon@hotmail.com>
 */
class ExecuteCommand extends Command
{
    protected CommandBus $commandBus;
    protected ScheduledCommandProcessor $scheduledCommandProcessor;
    protected LoggerInterface $logger;
    protected EntityManager  $em;
    protected bool $dumpMode = false;
    protected ?int $stopWorkSignalReceived = null;

    /**
     * @var int
     */
    private int $commandsVerbosity;

    /**
     * ExecuteCommand constructor.
     *
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        CommandBus $commandBus,
        LoggerInterface $cronLogger,
        ScheduledCommandProcessor $scheduledCommandProcessor
    )
    {
        if (class_exists(DukecityScheduledCommand::class)) {
            $this->em = $managerRegistry->getManagerForClass(DukecityScheduledCommand::class);
        } else {
            $this->em = $managerRegistry->getManagerForClass(JMoseScheduledCommand::class);
        }
        $this->commandBus = $commandBus;
        $this->logger = $cronLogger;
        $this->scheduledCommandProcessor = $scheduledCommandProcessor;

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
        $this
            ->setName('scheduler:execute')
            ->setDescription('Execute scheduled commands')
            ->addOption('dump', null, InputOption::VALUE_NONE, 'Display next execution')
            ->addOption('no-output', null, InputOption::VALUE_NONE, 'Disable output message from scheduler')
            ->addOption('sync', InputOption::VALUE_OPTIONAL)
            ->setHelp('This class is the entry point to execute all scheduled command');
    }

    public function handleSystemSignal($signo)
    {
        $this->logger->debug('Stop signal received', ['signo' => $this->stopWorkSignalReceived]);
        $this->stopWorkSignalReceived = $signo;
    }

    /**
     * Initialize parameters and services used in execute function.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dumpMode = $input->getOption('dump');

        // Store the original verbosity before apply the quiet parameter
        $this->commandsVerbosity = $output->getVerbosity();

        if (true === $input->getOption('no-output')) {
            $output->setVerbosity(OutputInterface::VERBOSITY_QUIET);
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->logger->info('Start : '.($this->dumpMode ? 'Dump' : 'Execute').' all scheduled command');

        if (class_exists(DukecityScheduledCommand::class)) {
            $commands = $this->em->getRepository(DukecityScheduledCommand::class)->findBy(['disabled' => false], ['priority' => 'DESC']);
        } else {
            $commands = $this->em->getRepository(JMoseScheduledCommand::class)->findBy(['disabled' => false], ['priority' => 'DESC']);
        }

        $noneExecution = true;
        foreach ($commands as $command) {
            $this->scheduledCommandProcessor->setCommand($command);
            if ($this->stopWorkSignalReceived !== null) {
                return $this->stopWorkSignalReceived;
            }

            try {
                /** @var ScheduledCommand $command */
                $cron = CronExpression::factory($command->getCronExpression());
                $nextRunDate = $cron->getNextRunDate($command->getLastExecution());
                $now = new \DateTime();

                if ($command->isExecuteImmediately()) {
                    $noneExecution = false;
                    $this->logger->info('Immediately execution asked');

                    if (!$input->getOption('dump')) {
                        $this->executeCommand($command, $output, $input);
                    }
                } elseif ($nextRunDate < $now) {
                    $noneExecution = false;

                    if ($input->getOption('dump')) {
                        $output->writeln(
                            'Command <comment>' . $command->getCommand() .
                            '</comment> should be executed - last execution : <comment>' .
                            $command->getLastExecution()->format(\DateTimeInterface::ATOM) . '.</comment>'
                        );
                    } else {
                        $this->logger->info(
                            'Command should be executed',
                            ['last_execution' => $command->getLastExecution()->format(\DateTimeInterface::ATOM)]
                        );
                        $this->executeCommand($command, $output, $input);
                    }
                }
            } catch (\Throwable $e) {
                $this->logger->critical($e);
                continue;
            }
        }

        if (true === $noneExecution) {
            $this->logger->debug('Nothing to do.');
        }

        return 0;
    }

    /**
     * @param JMoseScheduledCommand|DukecityScheduledCommand $scheduledCommand
     */
    private function executeCommand(object $scheduledCommand, OutputInterface $output, InputInterface $input)
    {
        try {
            $consoleCommand = $this->getApplication()->find($scheduledCommand->getCommand());
        } catch (\InvalidArgumentException $e) {
            $scheduledCommand->setLastReturnCode(-1);
            $this->logger->error('Cannot find command');
            $this->em->flush();

            return;
        }

        $command = new ScheduledCommandCommand($scheduledCommand->getId());
        $command->setAsync(!$input->getOption('sync'));

        $this->commandBus->handle($command);
    }
}
