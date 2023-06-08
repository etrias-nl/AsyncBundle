<?php

namespace Tests\Etrias\AsyncBundle\Integration\Messenger\Transport;

use Revolt\EventLoop;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
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
        $this->testMessageHandling(0, 1);
    }

//    public function testDispatchOneMessageToNatsWhenWorkerIsAlreadyRunning()
//    {
//        $this->testMessageHandling(1, 0);
//    }

    public function testTimeout()
    {

    }

    public function testReconnectAfterTimeout()
    {

    }

    public function testExactlyOnceDelivery()
    {

    }

    private function testMessageHandling(int $delayPublisher = 0, int $delayWorker = 0)
    {
        $eventBusSetup = new EventbusSetup();
        $message = uniqid('Message_');

        EventLoop::delay($delayPublisher, function () use ($eventBusSetup, $message): void {
            $this->runPublisher($eventBusSetup, $message);
        });

        EventLoop::delay($delayWorker, function () use ($eventBusSetup): void {

            $this->runWorker($eventBusSetup);
        });

        EventLoop::run();

        $this->assertCount(1, $eventBusSetup->getMessageResultStore());
        $this->assertSame('Handled '.$message, $eventBusSetup->getMessageResultStore()->getIterator()[0]);
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

        $this->assertNotNull($envelope->last(SentStamp::class));
    }
}
