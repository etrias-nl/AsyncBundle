<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Logger;

use JMose\CommandSchedulerBundle\Entity\ScheduledCommand as JMoseScheduledCommand;
use Dukecity\CommandSchedulerBundle\Entity\ScheduledCommand as DukecityScheduledCommand;
use Monolog\LogRecord;

if (class_exists(DukecityScheduledCommand::class)) {
    class ScheduledCommandProcessor
    {
        private ?DukecityScheduledCommand $command = null;

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

        public function setCommand(?DukecityScheduledCommand $command): void
        {
            $this->command = $command;
        }
    }
} else {
    class ScheduledCommandProcessor
    {
        private ?JMoseScheduledCommand $command = null;

        /**
         * @param array $record
         *
         * @return array
         */
        public function __invoke($record)
        {
            if (!$this->command) {
                return $record;
            }

            $record['extra']['commandId'] = $this->command->getId();
            $record['extra']['command'] = $this->command->getCommand();
            $record['extra']['args'] = $this->command->getArguments();

            return $record;
        }

        public function setCommand(?JMoseScheduledCommand $command): void
        {
            $this->command = $command;
        }
    }
}
