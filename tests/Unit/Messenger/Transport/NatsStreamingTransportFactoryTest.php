<?php

namespace Tests\Etrias\AsyncBundle\Unit\Messenger\Transport;

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Etrias\AsyncBundle\Messenger\Transport\NatsStreamingTransport;
use Etrias\AsyncBundle\Messenger\Transport\NatsStreamingTransportFactory;
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
        $transport = $this->factory->createTransport('nats://username:password@nats:4222?stream=queue1&inbox=inbox1&non_processed=value', [], $this->serializer);

        $this->assertEquals(
            new NatsStreamingTransport(
                new Client(
                    new Configuration(
                        [
                            'host' => 'nats',
                            'port' => 4222,
                            'user' => 'username',
                            'pass' => 'password'
                        ]
                    )
                ),
                $this->serializer,
                'queue1',
                'queue1',
                'inbox1'
            ),
            $transport
        );
    }
}
