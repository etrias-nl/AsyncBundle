<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Command;


use Etrias\Bundles\CoreCQRSBundle\Command\CommandInterface;

class UserAwareCommandWrapper implements CommandInterface, WrappedCommandInterface
{

    /**
     * @var CommandInterface
     */
    protected $command;

    /**
     * @var int
     */
    protected $userId;

    public function __construct(CommandInterface $command, int $userId)
    {
        $this->command = $command;
        $this->userId = $userId;
    }

    /**
     * @return CommandInterface
     */
    public function getCommand(): CommandInterface
    {
        return $this->command;
    }

    /**
     * @param CommandInterface $command
     * @return UserAwareCommandWrapper
     */
    public function setCommand(CommandInterface $command): UserAwareCommandWrapper
    {
        $this->command = $command;

        return $this;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return UserAwareCommandWrapper
     */
    public function setUserId(int $userId): UserAwareCommandWrapper
    {
        $this->userId = $userId;

        return $this;
    }


}
