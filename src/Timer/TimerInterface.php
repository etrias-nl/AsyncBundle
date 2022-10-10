<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Timer;

interface TimerInterface
{
    public function lap(): array;
}
