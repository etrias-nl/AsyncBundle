<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Command;


use Etrias\CqrsBundle\Command\CommandInterface;

interface WrappedCommandInterface
{
    public function getCommand(): CommandInterface;
}
