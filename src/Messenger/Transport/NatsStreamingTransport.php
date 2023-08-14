<?php

namespace Etrias\AsyncBundle\Messenger\Transport;

use Basis\Nats\Client;
use Basis\Nats\Consumer\Consumer;
use Basis\Nats\Message\Payload;
use Basis\Nats\Stream\RetentionPolicy;
use Basis\Nats\Stream\StorageBackend;
use Basis\Nats\Stream\Stream;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Receiver\MessageCountAwareInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;
use Symfony\Component\Messenger\Transport\SetupableTransportInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Contracts\Service\ResetInterface;

class NatsStreamingTransport implements TransportInterface, SetupableTransportInterface, MessageCountAwareInterface
{
    protected Client $client;
    protected SerializerInterface $serializer;
    protected string $streamName;
    protected ?Stream $stream = null;
    protected ?Consumer $consumer = null;
    protected string $subject;
    protected ?string $inbox;
    protected ?int $timeout;
    private bool $deduplication;

    public function __construct(
        Client              $client,
        SerializerInterface $serializer,
        string              $streamName,
        string              $subject = null,
        string              $inbox = null,
        int                 $timeout = null,
        bool                $deduplication = false
    )
    {
        $this->client = $client;
        $this->serializer = $serializer;
        $this->streamName = $streamName;
        $this->subject = $subject ?? $streamName;
        $this->inbox = $inbox;
        $this->timeout = $timeout;
        $this->deduplication = $deduplication;
    }

    public function get(): iterable
    {
        $receivedMessages = [];

        try {
            $this->connect();
            // consumer would be created on first handle call
            $this->consumer->handle(function (Payload $message) use (&$receivedMessages) {
                $receivedMessages[] = $this->serializer->decode(['body' => $message->body]);
            });
        } catch (\Throwable $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        return $receivedMessages;
    }

    public function ack(Envelope $envelope): void
    {
        //no-op, because ack is already handled by get.
        //TODO ack must be arranged for correct exactly once delivery
    }

    public function reject(Envelope $envelope): void
    {
        throw new InvalidArgumentException('You cannot call reject() on the Messenger NatsTransport.');
    }

    public function send(Envelope $envelope): Envelope
    {
        $encodedMessage = $this->serializer->encode($envelope);

        try {
            $this->connect();

            $body = $encodedMessage['body'];
            $messageId = $this->deduplication ? $this->hashMessage($body) : Uuid::uuid4();
            $payload = new Payload($body, [
                'Nats-Msg-Id' => $messageId
            ]);

            $this->client->publish($this->subject, $payload, $this->inbox);
        } catch (\Throwable $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        return $envelope->with(new TransportMessageIdStamp($messageId));
    }

    private function connect()
    {
        $this->client->ping();
    }

    private function hashMessage(string $body): string
    {
        $algo = 'sha1';

        if (version_compare(PHP_VERSION, '8.1.0', '>=')) {
            $algo = 'xxh128';
        }

        return hash($algo, $body);
    }

    public function getMessageCount(): int
    {
        // TODO: Implement getMessageCount() method.
    }

    public function setup(): void
    {
        $this->stream = $this->client->getApi()->getStream($this->streamName);
        //TODO make it configurable
        $this->stream->getConfiguration()
            ->setRetentionPolicy(RetentionPolicy::WORK_QUEUE)
            ->setStorageBackend(StorageBackend::MEMORY)
            ->setSubjects([$this->subject]);

        $this->stream->createIfNotExists();

        $this->consumer = $this->stream->getConsumer($this->streamName);
        $this->consumer->getConfiguration()->setSubjectFilter($this->subject);
        $this->consumer->setIterations(1);
    }
}