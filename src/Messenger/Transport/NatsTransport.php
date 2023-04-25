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
        dd('get');
        $this->client->subscribe();
        // TODO: Implement get() method.
    }

    public function ack(Envelope $envelope): void
    {
        $this->connect();
        dd('ack');
        // TODO: Implement ack() method.
    }

    public function reject(Envelope $envelope): void
    {
        $this->connect();
        dd('reject');
        // TODO: Implement reject() method.
    }

    public function reset()
    {
        $this->connect();
        dd('reset');
        // TODO: Implement reset() method.
    }

    public function send(Envelope $envelope): Envelope
    {
        $this->connect();

        $this->client->publish('foo', 'Marty McFly');
        dd('send');
        // TODO: Implement send() method.
    }

    private function connect()
    {
        if (!$this->client->isConnected()) {
            $a = $this->client->connect($this->timeout);
            $b  = 1;
        }
    }
}