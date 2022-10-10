<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Timer;

class NullTimer implements TimerInterface
{
    public function lap(): array
    {
        return [];
    }
}
