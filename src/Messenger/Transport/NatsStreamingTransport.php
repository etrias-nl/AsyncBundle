<?php

namespace Etrias\AsyncBundle\Messenger\Transport;

use NatsStreaming\Connection;
use Nats\Message;
use NatsStreaming\SubscriptionOptions;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Contracts\Service\ResetInterface;

class NatsStreamingTransport implements TransportInterface, ResetInterface
{
    protected Connection $client;
    protected SerializerInterface $serializer;
    protected string $subject;
    protected ?string $inbox;
    protected ?int $timeout;

    public function __construct(
        Connection $client,
        SerializerInterface $serializer,
        string $subject,
        string $inbox = null,
        int $timeout = null
    )
    {
        $this->client = $client;
        $this->serializer = $serializer;
        $this->subject = $subject;
        $this->inbox = $inbox;
        $this->timeout = $timeout;
    }

    public function get(): iterable
    {
        $receivedMessages = [];

        $this->connect();
        $subscriptionOptions = new SubscriptionOptions();
        $subscription = $this->client->subscribe(
            $this->subject,
            function(Message $message) use (&$receivedMessages) {
                $receivedMessages[] = $this->serializer->decode(['body' => $message->getBody()]);
            },
            $subscriptionOptions
        );

        $subscription->wait(1);
//        $this->client->close();

        #todo try/catch
        var_dump($receivedMessages);
        return $receivedMessages;
    }

    public function ack(Envelope $envelope): void
    {
        //no-op, because ack is already handled by get.
    }

    public function reject(Envelope $envelope): void
    {
        throw new InvalidArgumentException('You cannot call reject() on the Messenger NatsTransport.');
    }

    public function reset()
    {
        // no-op
    }

    public function send(Envelope $envelope): Envelope
    {
        $encodedMessage = $this->serializer->encode($envelope);

        try {
            $this->connect();
            $natsRequest = $this->client->publish($this->subject, $encodedMessage['body'], $this->inbox);
        } catch (\Throwable $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        $gotAck = $natsRequest->wait();
        if (!$gotAck) {
            throw new TransportException('Message not acked by server');
        }

        //If possible we want to add a TransportMessageIdStamp with the message id

        return $envelope;
    }

    private function connect()
    {
        if (!$this->client->isConnected()) {
            $this->client->connect($this->timeout);
        }
    }
}