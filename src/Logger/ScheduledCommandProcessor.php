<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Logger;



use JMose\CommandSchedulerBundle\Entity\ScheduledCommand;
use Monolog\LogRecord;

class ScheduledCommandProcessor
{
    /**
     * @var ScheduledCommand|\Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand|null
     */
    private ?object $command = null;

    /**
     * @param array|LogRecord $record
     *
     * @return array|LogRecord
     */
    public function __invoke($record)
    {
        if (!$this->command) {
            return $record;
        }

        if ($record instanceof LogRecord) {
            $extra = $record->extra;

            $extra['commandId'] = $this->command->getId();
            $extra['command'] = $this->command->getCommand();
            $extra['args'] = $this->command->getArguments();

            return $record->with(extra: $extra);
        }

        $record['extra']['commandId'] = $this->command->getId();
        $record['extra']['command'] = $this->command->getCommand();
        $record['extra']['args'] = $this->command->getArguments();

        return $record;
    }

    /**
     * @param ScheduledCommand|\Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand|null $command
     */
    public function setCommand(?object $command): void
    {
        $this->command = $command;
    }
}
