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
        $stripInternalKeys = false;
        if ($value instanceof \Traversable) {
            $value = iterator_to_array($value);
        }

        if (is_object($value)) {
            $stripInternalKeys = true;
            $value = (array) $value;
        }

        if (is_array($value)) {
            $result = [];
            foreach ($value as $k => $v) {
                if ($stripInternalKeys && false !== $pos = strrpos($k, "\0")) {
                    $k = substr($k, $pos + 1);
                }

                $result[$k] = self::serialize($v);
            }

            $value = $result;
        }

        return $value;
    }
}
