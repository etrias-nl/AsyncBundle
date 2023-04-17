<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Logger;



use JMose\CommandSchedulerBundle\Entity\ScheduledCommand;

class ScheduledCommandProcessor
{
    /**
     * @var ScheduledCommand|\Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand|null
     */
    private ?object $command = null;

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
     * @param ScheduledCommand|\Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand|null $command
     */
    public function setCommand(?object $command): void
    {
        $this->command = $command;
    }
}
