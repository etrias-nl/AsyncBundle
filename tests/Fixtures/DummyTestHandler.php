<?php

namespace Tests\Etrias\AsyncBundle\Fixtures;

use Symfony\Component\Messenger\Envelope;
use parallel\Channel;

class DummyTestHandler
{
    private $timesCalled = 0;
    private Channel $channel;
    private $shouldThrow;

    public function __construct(Channel $channel, bool $shouldThrow)
    {
        $this->channel = $channel;
        $this->shouldThrow = $shouldThrow;
    }

    public function __invoke(DummyMessage $message)
    {
        ++$this->timesCalled;

        if ($this->shouldThrow) {
            throw new \Exception('Failure from call '.$this->timesCalled);
        }

        $this->channel->Send('Handled ' . $message->getMessage());
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