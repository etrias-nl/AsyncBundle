<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Command;


use Etrias\CqrsBundle\Command\CommandInterface;

class UserAwareCommandWrapper implements CommandInterface, WrappedCommandInterface
{

    /**
     * @var CommandInterface
     */
    protected $command;

    /**
     * @var int|string|int[]|string[]
     */
    protected $userId;

    /**
     * @param int|string|int[]|string[] $userId
     */
    public function __construct(CommandInterface $command, $userId)
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
     * @return int|string|int[]|string[]
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int|string|int[]|string[] $userId
     * @return UserAwareCommandWrapper
     */
    public function setUserId($userId): UserAwareCommandWrapper
    {
        $this->userId = $userId;

        return $this;
    }


}
