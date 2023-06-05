<?php

namespace Tests\Etrias\AsyncBundle\Fixtures;

class DummyTestHandler
{
    private $timesCalled = 0;
    private $shouldThrow;
    private MessageResultStore $messageResultStore;

    public function __construct(MessageResultStore $messageResultStore, bool $shouldThrow)
    {
        $this->shouldThrow = $shouldThrow;
        $this->messageResultStore = $messageResultStore;
    }

    public function __invoke(DummyMessage $message)
    {
        ++$this->timesCalled;

        if ($this->shouldThrow) {
            throw new \Exception('Failure from call '.$this->timesCalled);
        }

        $this->messageResultStore->addResult('Handled '.$message->getMessage());
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