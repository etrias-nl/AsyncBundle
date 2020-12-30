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

    public function getCommand(): CommandInterface
    {
        return $this->command;
    }

    public function setCommand(CommandInterface $command): self
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
     */
    public function setUserId($userId): self
    {
        $this->userId = $userId;

        return $this;
    }
}
