<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Handler\CommandNameExtractor;

use Etrias\AsyncBundle\Command\WrappedCommandInterface;
use League\Tactician\Handler\CommandNameExtractor\CommandNameExtractor;

class ClassNameExtractor implements CommandNameExtractor
{
    /**
     * {@inheritdoc}
     */
    public function extract($command)
    {
        if ($command instanceof WrappedCommandInterface) {
            return \get_class($command->getCommand());
        }

        return \get_class($command);
    }
}
