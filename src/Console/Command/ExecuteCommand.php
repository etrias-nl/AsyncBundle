<?php

namespace Etrias\AsyncBundle\Console\Command;

use Cron\CronExpression;
use Doctrine\Persistence\ManagerRegistry;
use Etrias\AsyncBundle\Command\ScheduledCommandCommand;
use JMose\CommandSchedulerBundle\Entity\ScheduledCommand;
use League\Tactician\CommandBus;
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
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;

    /**
     * @var bool
     */
    private $dumpMode;

    /**
     * @var int
     */
    private $commandsVerbosity;

    /**
     * ExecuteCommand constructor.
     *
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry, CommandBus $commandBus)
    {
        $this->em = $managerRegistry->getManagerForClass(ScheduledCommand::class);
        $this->commandBus = $commandBus;

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
        $output->writeln('<info>Start : '.($this->dumpMode ? 'Dump' : 'Execute').' all scheduled command</info>');

        $commands = $this->em->getRepository(ScheduledCommand::class)->findBy(['disabled' => false], ['priority' => 'DESC']);

        $noneExecution = true;
        foreach ($commands as $command) {
            /** @var ScheduledCommand $command */
            $cron = CronExpression::factory($command->getCronExpression());
            $nextRunDate = $cron->getNextRunDate($command->getLastExecution());
            $now = new \DateTime();

            if ($command->isExecuteImmediately()) {
                $noneExecution = false;
                $output->writeln(
                    'Immediately execution asked for : <comment>'.$command->getCommand().'</comment>'
                );

                if (!$input->getOption('dump')) {
                    $this->executeCommand($command, $output, $input);
                }
            } elseif ($nextRunDate < $now) {
                $noneExecution = false;
                $output->writeln(
                    'Command <comment>'.$command->getCommand().
                    '</comment> should be executed - last execution : <comment>'.
                    $command->getLastExecution()->format(\DateTimeInterface::ATOM).'.</comment>'
                );

                if (!$input->getOption('dump')) {
                    $this->executeCommand($command, $output, $input);
                }
            }
        }

        if (true === $noneExecution) {
            $output->writeln('Nothing to do.');
        }

        return 0;
    }

    /**
     * @param ScheduledCommand $scheduledCommand
     * @param OutputInterface  $output
     * @param InputInterface   $input
     */
    private function executeCommand(ScheduledCommand $scheduledCommand, OutputInterface $output, InputInterface $input)
    {
        try {
            $consoleCommand = $this->getApplication()->find($scheduledCommand->getCommand());
        } catch (\InvalidArgumentException $e) {
            $scheduledCommand->setLastReturnCode(-1);
            $output->writeln('<error>Cannot find '.$scheduledCommand->getCommand().'</error>');
            $this->em->flush();

            return;
        }

        $command = new ScheduledCommandCommand($scheduledCommand->getId());
        $command->setAsync(!$input->getOption('sync'));

        $this->commandBus->handle($command);
    }
}
