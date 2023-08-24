<?php

namespace Etrias\AsyncBundle\Messenger\Transport;

use Basis\Nats\Client;
use Basis\Nats\Configuration;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;

class NatsStreamingTransportFactory implements TransportFactoryInterface
{

    public function createTransport(string $dsn, array $options, SerializerInterface $serializer): TransportInterface
    {
        $urlParts = parse_url($dsn);

        $queryParts = [];
        parse_str($urlParts['query'] ?? '', $queryParts);

        if (!key_exists('stream', $queryParts)) {
            throw new \InvalidArgumentException('Connection string must contain "stream" in querystring');
        }

        $options = array_intersect_key($urlParts, array_flip(['host', 'port', 'user', 'pass']));

        $configuration = new Configuration($options);

        $client = new Client($configuration);

        return new NatsStreamingTransport(
            $client,
            $serializer,
            $queryParts['stream'],
            $queryParts['subject'] ?? null,
            $queryParts['inbox'] ?? null,
            $queryParts['timeout'] ?? null,
            $options['deduplication'] ?? false
        );
    }

    public function supports(string $dsn, array $options): bool
    {
        return 0 === strpos($dsn, 'natsstreaming://');
    }
}