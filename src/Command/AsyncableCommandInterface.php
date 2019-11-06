<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Command;


use Etrias\Bundles\CoreCQRSBundle\Command\CommandInterface;

interface AsyncableCommandInterface extends CommandInterface
{
    /**
     * @return bool
     */
    public function isAsync(): bool;
}
