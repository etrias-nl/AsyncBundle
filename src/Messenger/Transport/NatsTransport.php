<?php

namespace Etrias\AsyncBundle\Messenger\Transport;

use Nats\Connection;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Contracts\Service\ResetInterface;

class NatsTransport implements TransportInterface, ResetInterface
{
    protected Connection $client;
    protected SerializerInterface $serializer;
    protected ?int $timeout;

    public function __construct(Connection $client, SerializerInterface $serializer, int $timeout = null)
    {
        $this->client = $client;
        $this->serializer = $serializer;
        $this->timeout = $timeout;
    }

    public function get(): iterable
    {
        $this->connect();
        $this->client->subscribe();
        // TODO: Implement get() method.
    }

    public function ack(Envelope $envelope): void
    {
        // TODO: Implement ack() method.
    }

    public function reject(Envelope $envelope): void
    {
        // TODO: Implement reject() method.
    }

    public function reset()
    {
        // TODO: Implement reset() method.
    }

    public function send(Envelope $envelope): Envelope
    {
        // TODO: Implement send() method.
    }

    private function connect()
    {
        if (!$this->client->isConnected()) {
            $this->client->connect($this->timeout);
        }
    }
}