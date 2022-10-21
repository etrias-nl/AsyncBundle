<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Logger;



use JMose\CommandSchedulerBundle\Entity\ScheduledCommand;

class ScheduledCommandProcessor
{
    private ?ScheduledCommand $command = null;

    public function __invoke(array $record)
    {

        if ($this->command) {
            $record['extra']['commandId'] = $this->command->getId();
            $record['extra']['command'] = $this->command->getCommand();
            $record['extra']['args'] = $this->command->getArguments();
        }

        return $record;
    }

    /**
     * @param ?ScheduledCommand $command
     */
    public function setCommand(?ScheduledCommand $command): void
    {
        $this->command = $command;
    }
}
