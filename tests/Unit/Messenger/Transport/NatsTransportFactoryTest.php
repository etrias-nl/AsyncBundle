<?php

namespace Tests\Etrias\AsyncBundle\Unit\Messenger\Transport;

use Etrias\AsyncBundle\Messenger\Transport\NatsTransport;
use Etrias\AsyncBundle\Messenger\Transport\NatsTransportFactory;
use Nats\Connection;
use Nats\ConnectionOptions;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class NatsTransportFactoryTest extends TestCase
{
    private NatsTransportFactory $factory;
    private SerializerInterface $serializer;

    public function setUp(): void
    {
        $this->factory = new NatsTransportFactory();
        $this->serializer = $this->createMock(SerializerInterface::class);
    }

    public function testSupportsTransport()
    {
        $this->assertTrue($this->factory->supports('nats://localhost:4222', []));
        $this->assertFalse($this->factory->supports('sync://', []));
    }

    public function testCreateTransport()
    {
        $transport = $this->factory->createTransport('nats://username:password@nats:4222?non_processed=value', [], $this->serializer);

        $this->assertEquals(
            new NatsTransport(
                new Connection(
                    new ConnectionOptions(
                        [
                            'host' => 'nats',
                            'port' => '4222',
                            'user' => 'username',
                            'pass' => 'password'
                        ]
                    )
                ),
                $this->serializer
            ),
        $transport);
    }
}
