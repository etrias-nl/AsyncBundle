<?php

use Etrias\AsyncBundle\Messenger\Transport\NatsTransport;
use Nats\Connection;
use PHPUnit\Framework\MockObject\Generator as MockGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\AnyInvokedCount as AnyInvokedCountMatcher;
use Symfony\Component\DependencyInjection\ContainerInterface;
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
use Tests\Etrias\AsyncBundle\Fixtures\DummyMessage;
use Tests\Etrias\AsyncBundle\Fixtures\DummyTestHandler;

require_once(__DIR__.'/../../vendor/autoload.php');

$client = new Connection(new \Nats\ConnectionOptions([
    'host' => 'nats'
]));
$serializer = new PhpSerializer();

$mockGenerator = new MockGenerator();

$natsTransport = new NatsTransport($client, $serializer, 'queue', timeout: 3);

function createMock(string $originalClassName): MockObject
{
    $mockGenerator = new MockGenerator();

    return $mockGenerator->getMock(
        $originalClassName,
        callOriginalConstructor: false,
        callOriginalClone: false,
        cloneArguments: false,
        allowMockingUnknownTypes: false
    );
}

$transports = [
    'nats' => $natsTransport,
];

$locator = createMock(ContainerInterface::class);
$locator->expects(new AnyInvokedCountMatcher())
    ->method('has')
    ->willReturn(true);
$locator->expects(new AnyInvokedCountMatcher())
    ->method('get')
    ->willReturnCallback(function ($transportName) use ($transports) {
        return $transports[$transportName];
    });
$senderLocator = new SendersLocator(
    [DummyMessage::class => ['nats']],
    $locator
);

// retry strategy with zero retries so it goes to the failed transport after failure
$retryStrategyLocator = createMock(ContainerInterface::class);
$retryStrategyLocator->expects(new AnyInvokedCountMatcher())
    ->method('has')
    ->willReturn(true);
$retryStrategyLocator->expects(new AnyInvokedCountMatcher())
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