<?php

namespace Tests\Etrias\AsyncBundle\Fixtures;

class DummyTestHandler
{
    private $timesCalled = 0;
    private $shouldThrow;

    public function __construct(bool $shouldThrow)
    {
        $this->shouldThrow = $shouldThrow;
    }

    public function __invoke()
    {
        ++$this->timesCalled;

        if ($this->shouldThrow) {
            throw new \Exception('Failure from call '.$this->timesCalled);
        }
    }

    public function getTimesCalled(): int
    {
        return $this->timesCalled;
    }

    public function setShouldThrow(bool $shouldThrow)
    {
        $this->shouldThrow = $shouldThrow;
    }
}