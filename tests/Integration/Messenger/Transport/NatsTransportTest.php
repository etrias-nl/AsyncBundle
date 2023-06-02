<?php

namespace Tests\Etrias\AsyncBundle\Integration\Messenger\Transport;

use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Worker;
use Tests\Etrias\AsyncBundle\Fixtures\DummyMessage;
use parallel\Runtime;
use parallel\Channel;
use Tests\Etrias\AsyncBundle\Fixtures\EventbusSetup;

class NatsTransportTest extends KernelTestCase
{
    /** @var Future[]  */
    private array $futuresList = [];

    public function testDispatchToNats()
    {
        $channel = new Channel();

        $threadedWorker = new Runtime();
        $this->futuresList[] = $threadedWorker->run(self::getWorkerTask(), [$channel]);

        $message = uniqid('Message_');
        $threadedPublisher = new Runtime();
        $this->futuresList[] = $threadedPublisher->run(self::getPublisherTask(), [$channel, $message]);

        $this->assertSame('Handled '.$message, $channel->recv());
        $channel->close();
    }

    public function testTimeout()
    {

    }

    public function testReconnectAfterTimeout()
    {

    }

    static private function getWorkerTask(): \Closure
    {
        return function (Channel $channel) {
            require_once(__DIR__.'/../../../../vendor/autoload.php');
            $eventBusSetup = new EventbusSetup($channel);

            $throwable = null;
            $failedListener = function (WorkerMessageFailedEvent $event) use (&$throwable) {
                $throwable = $event->getThrowable();
            };
            $eventBusSetup->getEventDispatcher()->addListener(WorkerMessageFailedEvent::class, $failedListener);


            $worker = new Worker(
                $eventBusSetup->getTransports(),
                $eventBusSetup->getMessageBus(),
                $eventBusSetup->getEventDispatcher());

            $worker->run();
        };
    }

    static private function getPublisherTask(): \Closure
    {
        return function(Channel $channel, $message) {
            require_once(__DIR__.'/../../../../vendor/autoload.php');
            $eventBusSetup = new EventbusSetup($channel);

            $envelope = new Envelope(new DummyMessage($message));
            $envelope = $eventBusSetup->getMessageBus()->dispatch($envelope);

            Assert::assertNotNull($envelope->last(SentStamp::class));
        };
    }
}
