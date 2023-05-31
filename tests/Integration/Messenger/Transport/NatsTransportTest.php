<?php

namespace Tests\Etrias\AsyncBundle\Integration\Messenger\Transport;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageFailedEvent;
use Symfony\Component\Messenger\Worker;
use Tests\Etrias\AsyncBundle\Fixtures\DummyMessage;
use parallel\Runtime;
use parallel\Channel;
use Tests\Etrias\AsyncBundle\Fixtures\EventbusSetup;

class NatsTransportTest extends KernelTestCase
{

    public function testDispatchToNats()
    {
        $channel = new Channel();
        $eventBusSetup = new EventbusSetup($channel);


        $runWorker = function (Channel $channel) {
            require_once(__DIR__.'/../../../../vendor/autoload.php');
            $eventBusSetup = new EventbusSetup($channel);

            $throwable = null;
            $failedListener = function (WorkerMessageFailedEvent $event) use (&$throwable) {
                $throwable = $event->getThrowable();
            };
            $eventBusSetup->getEventDispatcher()->addListener(WorkerMessageFailedEvent::class, $failedListener);


            $worker = new Worker(
                $eventBusSetup->getTransports(),
                $eventBusSetup->getMessageBus(),
                $eventBusSetup->getEventDispatcher());

            $worker->run();

            //return 'throwable';
        };

        $runDispatcher = function(Channel $channel) {
            require_once(__DIR__.'/../../../../vendor/autoload.php');
            $eventBusSetup = new EventbusSetup($channel);

            $envelope = new Envelope(new DummyMessage('API'));
            $envelope = $eventBusSetup->getMessageBus()->dispatch($envelope);
        };

        var_dump('before start worker');

        $threadedWorker = new Runtime();
        $threadedWorker->run($runWorker, [$channel, 'nats']);
        var_dump('after start worker');

        // send the message
        var_dump('before dispatch envelope');
        $threadedDispatcher = new Runtime();
        $threadedDispatcher->run($runDispatcher. [$channel]);
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
