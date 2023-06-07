<?php

namespace Tests\Etrias\AsyncBundle\Fixtures;

use Etrias\AsyncBundle\Messenger\Transport\NatsStreamingTransport;
use NatsStreaming\Connection;
use NatsStreaming\ConnectionOptions;
use PHPUnit\Framework\MockObject\Generator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount as AnyInvokedCountMatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Messenger\EventListener\AddErrorDetailsStampListener;
use Symfony\Component\Messenger\EventListener\SendFailedMessageForRetryListener;
use Symfony\Component\Messenger\EventListener\StopWorkerOnMessageLimitListener;
use Symfony\Component\Messenger\Handler\HandlerDescriptor;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Retry\MultiplierRetryStrategy;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class EventbusSetup
{
    private Generator $mockGenerator;

    private SerializerInterface $serializer;

    private Connection $natsClient;

    private NatsStreamingTransport $natsTransport;

    private array $transports;

    private ContainerInterface $sendersLocatorContainer;
    private SendersLocator $sendersLocator;

    private ContainerInterface $retryStrategyLocator;

    private HandlersLocator $handlersLocator;

    private MessageBus $messageBus;

    private EventDispatcher $eventDispatcher;

    private MessageResultStore $messageResultStore;

    public function __construct(string $natsHost = 'nats')
    {
        $this->mockGenerator = new Generator();
        $this->serializer = new PhpSerializer();
        $this->natsClient = new Connection(new ConnectionOptions([
            'natsOptions' => new \Nats\ConnectionOptions([
                'host' => $natsHost
            ])
        ]));
        $this->natsTransport = new NatsStreamingTransport($this->natsClient, $this->serializer, 'queue', timeout: 3);

        $this->transports = [
            'natsstreaming' => $this->natsTransport,
        ];
        $this->messageResultStore = new MessageResultStore();
        $this->setupSendersLocator();
        $this->setupRetryStrategyLocator();
        $this->setupHandlersLocator();
        $this->setupMessageBus();
        $this->setupEventDispatcher();
    }

    public function getEventDispatcher(): EventDispatcher
    {
        return $this->eventDispatcher;
    }

    public function getMessageBus(): MessageBus
    {
        return $this->messageBus;
    }

    public function getTransports(): array
    {
        return $this->transports;
    }

    public function getMessageResultStore(): MessageResultStore
    {
        return $this->messageResultStore;
    }

    private function createMock(string $originalClassName): MockObject
    {
        return $this->mockGenerator->getMock(
            $originalClassName,
            callOriginalConstructor: false,
            callOriginalClone: false,
            cloneArguments: false,
            allowMockingUnknownTypes: false
        );
    }

    private function setupSendersLocator(): void
    {
        $this->sendersLocatorContainer = $this->createMock(ContainerInterface::class);
        $this->sendersLocatorContainer->expects(new AnyInvokedCountMatcher())
            ->method('has')
            ->willReturn(true);
        $this->sendersLocatorContainer->expects(new AnyInvokedCountMatcher())
            ->method('get')
            ->willReturnCallback(function ($transportName) {
                return $this->transports[$transportName];
            });

        $this->sendersLocator = new SendersLocator(
            [DummyMessage::class => ['natsstreaming']],
            $this->sendersLocatorContainer
        );
    }

    private function setupRetryStrategyLocator(): void
    {
        // retry strategy with zero retries so it goes to the failed transport after failure
        $this->retryStrategyLocator = $this->createMock(ContainerInterface::class);
        $this->retryStrategyLocator->expects(new AnyInvokedCountMatcher())
            ->method('has')
            ->willReturn(true);
        $this->retryStrategyLocator->expects(new AnyInvokedCountMatcher())
            ->method('get')
            ->willReturn(new MultiplierRetryStrategy(0));
    }

    private function setupHandlersLocator(): void
    {
        $transportHandlerThatWorks = new DummyTestHandler($this->messageResultStore, false);

        $this->handlersLocator = new HandlersLocator([
            DummyMessage::class => [
                new HandlerDescriptor($transportHandlerThatWorks, [
                    'from_transport' => 'natsstreaming',
                ]),
            ],
        ]);
    }

    private function setupMessageBus(): void
    {
        $this->messageBus = new MessageBus([
            new SendMessageMiddleware($this->sendersLocator),
            new HandleMessageMiddleware($this->handlersLocator),
        ]);
    }

    private function setupEventDispatcher(): void
    {
        $this->eventDispatcher = new EventDispatcher();

        $this->eventDispatcher->addSubscriber(new AddErrorDetailsStampListener());
        $this->eventDispatcher->addSubscriber(new SendFailedMessageForRetryListener($this->sendersLocatorContainer, $this->retryStrategyLocator));

//        $this->eventDispatcher->addSubscriber(new SendFailedMessageToFailureTransportListener($sendersLocatorFailureTransport));
        $this->eventDispatcher->addSubscriber(new StopWorkerOnMessageLimitListener(1));
    }
}