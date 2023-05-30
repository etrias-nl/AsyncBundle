<?php

namespace Tests\Etrias\AsyncBundle\Integration\Messenger\Transport;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Worker;
use Tests\Etrias\AsyncBundle\Fixtures\DummyMessage;
use parallel\Runtime;
use parallel\Channel;

class NatsTransportTest extends KernelTestCase
{

    public function testDispatchToNats()
    {
        require __DIR__.'/../../../Fixtures/setup_eventbus.php';

        $runWorker = function (string $transportName) {
            require __DIR__.'/../../../Fixtures/setup_eventbus.php';

            $throwable = null;
            $failedListener = function (WorkerMessageFailedEvent $event) use (&$throwable) {
                $throwable = $event->getThrowable();
            };
            $dispatcher->addListener(WorkerMessageFailedEvent::class, $failedListener);


            $worker = new Worker([$transportName => $transports[$transportName]], $bus, $dispatcher);

            $worker->run();

            //return 'throwable';
        };

        $runDispatcher = function() {
            require __DIR__.'/../../../Fixtures/setup_eventbus.php';

            $envelope = new Envelope(new DummyMessage('API'));
            $envelope = $bus->dispatch($envelope);

        };

        var_dump('before start worker');
        $channel = new Channel();
        $threadedWorker = new Runtime();
        $threadedWorker->run($runWorker, ['nats']);
        var_dump('after start worker');

        // send the message
        var_dump('before dispatch envelope');
        $threadedDispatcher = new Runtime();
        $threadedDispatcher->run($runDispatcher);
        var_dump('after dispatch envelope');

        $channel->close();
        var_dump('end');
    }

    public function testTimeout()
    {

    }

    public function testReconnectAfterTimeout()
    {

    }
}
