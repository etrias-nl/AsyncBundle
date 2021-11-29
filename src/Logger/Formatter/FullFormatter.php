<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Logger\Formatter;

use Etrias\AsyncBundle\Command\UserAwareCommandWrapper;
use Etrias\CqrsBundle\Command\QueryInterface;
use League\Tactician\Logger\Formatter\Formatter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class FullFormatter implements Formatter
{
    public function logCommandReceived(LoggerInterface $logger, $command): void
    {
        $workingCommand = self::getWorkingCommand($command);

        if ($workingCommand instanceof QueryInterface) {
            return;
        }

        $logger->log(
            LogLevel::INFO,
            sprintf('Command received: %s:%s', \get_class($workingCommand), spl_object_hash($command)),
            ['command' => self::serialize($command)]
        );
    }

    public function logCommandSucceeded(LoggerInterface $logger, $command, $returnValue): void
    {
        $workingCommand = self::getWorkingCommand($command);

        if ($workingCommand instanceof QueryInterface) {
            return;
        }

        $logger->log(
            LogLevel::INFO,
            sprintf('Command succeeded: %s:%s', \get_class($workingCommand), spl_object_hash($command))
        );
    }

    public function logCommandFailed(LoggerInterface $logger, $command, \Exception $e): void
    {
        $workingCommand = self::getWorkingCommand($command);

        if ($workingCommand instanceof QueryInterface) {
            return;
        }

        $logger->log(
            LogLevel::ERROR,
            sprintf('Command failed: %s:%s', \get_class($workingCommand), spl_object_hash($command)),
            ['exception' => $e]
        );
    }

    protected static function getWorkingCommand(object $command): object
    {
        return $command instanceof UserAwareCommandWrapper ? $command->getCommand() : $command;
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    protected static function serialize($value)
    {
        if (is_object($value)) {
            $value = $value instanceof \stdClass ? (array) $value : \Closure::bind(fn (): array => get_object_vars($this), $value)->call($value);
        }
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = self::serialize($v);
            }
        }

        return $value;
    }
}
