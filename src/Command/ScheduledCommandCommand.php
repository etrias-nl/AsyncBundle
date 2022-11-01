<?php

namespace Etrias\AsyncBundle\Command;

use Etrias\CqrsBundle\Command\CommandInterface;

class ScheduledCommandCommand implements CommandInterface, AsyncableCommandInterface
{
    use AsyncableCommandTrait;

    /**
     * @var int
     */
    protected $commandId;

    public function __construct(int $commandId)
    {
        $this->commandId = $commandId;
    }

    public function getCommandId(): int
    {
        return $this->commandId;
    }
}
