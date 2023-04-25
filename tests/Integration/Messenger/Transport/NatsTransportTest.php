<?php

namespace Tests\Etrias\AsyncBundle\Integration\Messenger\Transport;

use Etrias\AsyncBundle\Messenger\Transport\NatsTransport;
use Nats\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\SendMessageMiddleware;
use Symfony\Component\Messenger\Transport\Sender\SendersLocator;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Tests\Etrias\AsyncBundle\Fixtures\DummyMessage;

class NatsTransportTest extends KernelTestCase
{

    public function testDisPatchToNats()
    {

        $client = new Connection();
        $serializer = $this->createMock(SerializerInterface::class);

        $natsTransport = new NatsTransport($client, $serializer);

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

//        $handlerLocator = new HandlersLocator([
//            DummyMessage::class => [
//                new HandlerDescriptor($transport1HandlerThatFails, [
//                    'from_transport' => 'transport1',
//                ]),
//                new HandlerDescriptor($transport2HandlerThatFails, [
//                    'from_transport' => 'transport2',
//                ]),
//            ],
//        ]);


        $bus = new MessageBus([
            new SendMessageMiddleware($senderLocator),
//            new HandleMessageMiddleware($handlerLocator),
        ]);

        // send the message
        $envelope = new Envelope(new DummyMessage('API'));
        $bus->dispatch($envelope);
    }

    public function testTimeout()
    {

    }

    public function testReconnectAfterTimeout()
    {

    }
}
