<?php

namespace Etrias\AsyncBundle\Messenger\Transport;

use Nats\Connection;
use Nats\ConnectionOptions;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class NatsTransportFactory implements TransportFactoryInterface
{

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $urlParts = parse_url($dsn);

        $options = array_intersect_key($urlParts, array_flip(['host', 'port', 'user', 'pass']));

        $client = new Connection(
            new ConnectionOptions($options)
        );

        return new NatsTransport($client, $serializer);
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'nats://');
    }
}