<?php

namespace Tests\Etrias\AsyncBundle\Unit\Messenger\Transport;

use Etrias\AsyncBundle\Messenger\Transport\NatsStreamingTransport;
use Etrias\AsyncBundle\Messenger\Transport\NatsStreamingTransportFactory;
use NatsStreaming\Connection;
use NatsStreaming\ConnectionOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class NatsStreamingTransportFactoryTest extends TestCase
{
    private NatsStreamingTransportFactory $factory;
    private SerializerInterface $serializer;

    public function setUp(): void
    {
        $this->factory = new NatsStreamingTransportFactory();
        $this->serializer = $this->createMock(SerializerInterface::class);
    }

    public function testSupportsTransport()
    {
        $this->assertTrue($this->factory->supports('natsstreaming://localhost:4222', []));
        $this->assertFalse($this->factory->supports('sync://', []));
    }

    public function testCreateTransport()
    {
        $transport = $this->factory->createTransport('nats://username:password@nats:4222?subject=queue1&inbox=inbox1&non_processed=value', [], $this->serializer);

        $this->assertEquals(
            new NatsStreamingTransport(
                new Connection(
                    new ConnectionOptions(
                        [
                            'natsOptions' => new \Nats\ConnectionOptions(
                                [
                                    'host' => 'nats',
                                    'port' => '4222',
                                    'user' => 'username',
                                    'pass' => 'password'
                                ]
                            )
                        ]
                    )
                ),
                $this->serializer,
                'queue1',
                'inbox1'
            ),
        $transport);
    }
}
