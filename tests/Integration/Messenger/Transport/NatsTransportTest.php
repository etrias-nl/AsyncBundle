<?php

namespace Tests\Etrias\AsyncBundle\Integration\Messenger\Transport;

use PHPUnit\Framework\Assert;
use Revolt\EventLoop;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMessageLimitListener;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Tests\Etrias\AsyncBundle\Fixtures\DummyMessage;
use Tests\Etrias\AsyncBundle\Fixtures\EventbusSetup;

class NatsTransportTest extends KernelTestCase
{
    public function testDispatchOneMessageToNatsWhenWorkerIsStartedLater()
    {
        $eventBusSetup = new EventbusSetup();
        $message = uniqid('Message_');

        EventLoop::defer(function () use ($eventBusSetup, $message): void {
            $this->runPublisher($eventBusSetup, $message);
        });

        EventLoop::defer(function () use ($eventBusSetup): void {
            $this->runWorker($eventBusSetup);
        });

        EventLoop::run();

        $this->assertCount(1, $eventBusSetup->getMessageResultStore());
    }

//    public function testDispatchOneMessageToNatsWhenWorkerIsAlreadyRunning()
//    {
//        $channel = new Channel();
//
//        $message = uniqid('Message_');
//        $threadedPublisher = new Runtime(__DIR__.'/../../../../vendor/autoload.php');
//        $threadedPublisher->run(self::getPublisherTask(), [$channel, $message]);
//
//        $threadedWorker = new Runtime(__DIR__.'/../../../../vendor/autoload.php');
//        $threadedWorker->run(self::getWorkerTask(), [$channel]);
//
//        $this->assertSame('Handled '.$message, $channel->recv());
//        $channel->close();
//
//        $threadedWorker->kill();
//        $threadedPublisher->kill();
//    }

    public function testTimeout()
    {

    }

    public function testReconnectAfterTimeout()
    {

    }

    private function runWorker(EventbusSetup $eventBusSetup): void
    {
        $throwable = null;
        $failedListener = function (WorkerMessageFailedEvent $event) use (&$throwable) {
            $throwable = $event->getThrowable();
        };
        $eventBusSetup->getEventDispatcher()->addListener(WorkerMessageFailedEvent::class, $failedListener);

        $worker = new Worker(
            $eventBusSetup->getTransports(),
            $eventBusSetup->getMessageBus(),
            $eventBusSetup->getEventDispatcher()
        );

        $worker->run();
    }

    private function runPublisher(EventbusSetup $eventBusSetup, $message): void
    {
        $envelope = new Envelope(new DummyMessage($message));
        $envelope = $eventBusSetup->getMessageBus()->dispatch($envelope);
        $envelope = $eventBusSetup->getMessageBus()->dispatch($envelope);

        $this->assertNotNull($envelope->last(SentStamp::class));
    }
}
