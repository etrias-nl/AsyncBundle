<?php

namespace Tests\Etrias\AsyncBundle\Integration\Messenger\Transport;

use Etrias\AsyncBundle\Messenger\Transport\NatsTransport;
use Nats\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\EventListener\AddErrorDetailsStampListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageToFailureTransportListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMessageLimitListener;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Worker;
use Tests\Etrias\AsyncBundle\Fixtures\DummyMessage;
use Thread;

class NatsTransportTest extends KernelTestCase
{

    public function testDisPatchToNats()
    {

        $client = new Connection();
        $serializer = new PhpSerializer();

        $natsTransport = new NatsTransport($client, $serializer, 'queue');

        $transports = [
            'nats' => $natsTransport,
        ];

        $locator = $this->createMock(ContainerInterface::class);
        $locator->expects($this->any())
            ->method('has')
            ->willReturn(true);
        $locator->expects($this->any())
            ->method('get')
            ->willReturnCallback(function ($transportName) use ($transports) {
                return $transports[$transportName];
            });
        $senderLocator = new SendersLocator(
            [DummyMessage::class => ['nats']],
            $locator
        );

        // retry strategy with zero retries so it goes to the failed transport after failure
        $retryStrategyLocator = $this->createMock(ContainerInterface::class);
        $retryStrategyLocator->expects($this->any())
            ->method('has')
            ->willReturn(true);
        $retryStrategyLocator->expects($this->any())
            ->method('get')
            ->willReturn(new MultiplierRetryStrategy(0));


        $transportHandlerThatWorks = new DummyTestHandler(false);

        $handlerLocator = new HandlersLocator([
            DummyMessage::class => [
                new HandlerDescriptor($transportHandlerThatWorks, [
                    'from_transport' => 'nats',
                ]),
            ],
        ]);

        $dispatcher = new EventDispatcher();
        $bus = new MessageBus([
            new SendMessageMiddleware($senderLocator),
            new HandleMessageMiddleware($handlerLocator),
        ]);
        $dispatcher->addSubscriber(new AddErrorDetailsStampListener());
        $dispatcher->addSubscriber(new SendFailedMessageForRetryListener($locator, $retryStrategyLocator));

//        $dispatcher->addSubscriber(new SendFailedMessageToFailureTransportListener($sendersLocatorFailureTransport));
        $dispatcher->addSubscriber(new StopWorkerOnMessageLimitListener(1));

        $runWorker = function (string $transportName) use ($transports, $bus, $dispatcher): ?\Throwable {
            $throwable = null;
            $failedListener = function (WorkerMessageFailedEvent $event) use (&$throwable) {
                $throwable = $event->getThrowable();
            };
            $dispatcher->addListener(WorkerMessageFailedEvent::class, $failedListener);


            $worker = new Worker([$transportName => $transports[$transportName]], $bus, $dispatcher);

            $worker->run();

            return $throwable;
        };

        var_dump('before start worker');
        $threadedWorker = new ThreadedCallable($runWorker, ['nats']);
        $threadedWorker->start();
        var_dump('after start worker');

        // send the message
        var_dump('before dispatch envelope');
        $envelope = new Envelope(new DummyMessage('API'));
        $envelope = $bus->dispatch($envelope);
        var_dump('after dispatch envelope');

        $b = $threadedWorker->join();

        $a = 1;
        var_dump('end');
    }

    public function testTimeout()
    {

    }

    public function testReconnectAfterTimeout()
    {

    }
}

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

class ThreadedCallable extends Thread {
    protected $callable;
    protected iterable $args;

    public function __construct(callable $callable, iterable $args)
    {
        $this->callable = $callable;
        $this->args = $args;
    }

    public function run()
    {
        $this->callable(...$this->args);
    }
}