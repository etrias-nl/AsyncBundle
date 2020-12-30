<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Logger\Formatter;

use Etrias\AsyncBundle\Command\UserAwareCommandWrapper;
use Etrias\CqrsBundle\Command\QueryInterface;
use Exception;
use JMS\Serializer\SerializationContext;
use JMS\Serializer\SerializerInterface;
use League\Tactician\Logger\Formatter\Formatter;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class JmsFormatter implements Formatter
{
    /** @var SerializerInterface */
    protected $serializer;

    /**
     * JmsFormatter constructor.
     */
    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    /**
     * {@inheritdoc}
     */
    public function logCommandReceived(LoggerInterface $logger, $command): void
    {
        $workingCommand = $this->getWorkingCommand($command);

        if ($workingCommand instanceof QueryInterface) {
            return;
        }

        $commandHash = $this->getCommandHash($command);
        $jsonContext = $this->serializer->serialize($command, 'json', SerializationContext::create()->setGroups(['logging']));
        $context = json_decode($jsonContext, true);

        $logger->log(
            LogLevel::INFO,
            sprintf('Command received: %s:%s', \get_class($workingCommand), $commandHash),
            ['command' => $context]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function logCommandSucceeded(LoggerInterface $logger, $command, $returnValue): void
    {
        $workingCommand = $this->getWorkingCommand($command);

        if ($workingCommand instanceof QueryInterface) {
            return;
        }

        $commandHash = $this->getCommandHash($command);

        $logger->log(
            LogLevel::INFO,
            sprintf('Command succeeded: %s:%s', \get_class($workingCommand), $commandHash)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function logCommandFailed(LoggerInterface $logger, $command, Exception $e): void
    {
        $workingCommand = $this->getWorkingCommand($command);

        if ($workingCommand instanceof QueryInterface) {
            return;
        }

        $commandHash = $this->getCommandHash($command);
        $logger->log(
            LogLevel::ERROR,
            sprintf('Command failed: %s:%s', \get_class($workingCommand), $commandHash),
            ['exception' => $e]
        );
    }

    /**
     * @param object $command
     *
     * @return object
     */
    protected function getWorkingCommand($command)
    {
        if ($command instanceof UserAwareCommandWrapper) {
            return $command->getCommand();
        }

        return $command;
    }

    /**
     * @param object $command
     *
     * @return string
     */
    protected function getCommandHash($command)
    {
        return spl_object_hash($command);
    }
}
