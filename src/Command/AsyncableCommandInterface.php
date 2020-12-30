<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Command;

use Etrias\CqrsBundle\Command\CommandInterface;

interface AsyncableCommandInterface extends CommandInterface
{
    /**
     * @return bool
     */
    public function getAsync(): ?bool;

    public function setAsync(?bool $async): self;
}
