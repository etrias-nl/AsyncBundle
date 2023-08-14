<?php

namespace Tests\Etrias\AsyncBundle\Integration\Messenger\Transport;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Process\Process;
use Tests\Etrias\AsyncBundle\Fixtures\EventbusSetup;

class NatsTransportTest extends KernelTestCase
{
    public function testDispatchOneMessageToNats()
    {
        $this->testMessageHandling();
    }
//
    public function testDispatchOneMessageToNatsWhenWorkerIsStartedLater()
    {
        $this->testMessageHandling(1,1,0, 1);
    }

    public function testTimeout()
    {
        $process = $this->runWorker('unreachable.nats');
        do {
            // wait for the given time
            sleep(1);

            if (!$process->isRunning()) {
                $this->assertStringContainsString('Uncaught Exception', $process->getOutput());
            }
        } while ($process->isRunning());
    }

    public function testExactlyOnceDelivery()
    {
        $this->testMessageHandling(50, 2, 2);
    }

    public function testDeduplicationEnabled()
    {
        $duplicatedMessage = 'Same message';
        $results = [];
        $this->runPublisher($duplicatedMessage, 0, true);
        $this->runPublisher($duplicatedMessage, 0, true);

        $worker = $this->runWorker();
        $worker->wait(function ($type, $buffer) use (&$results): void {
            if (Process::OUT === $type) {
                $results[] = trim($buffer);
            }
        });

        $this->assertSame(['Handled Same message'], $results);
    }

    public function testDeduplicationDisabled()
    {
        $duplicatedMessage = 'Same message';
        $results = [];
        $this->runPublisher($duplicatedMessage, 0, false);
        $this->runPublisher($duplicatedMessage, 0, false);

        $worker = $this->runWorker();
        $worker->wait(function ($type, $buffer) use (&$results): void {
            if (Process::OUT === $type) {
                $results[] = trim($buffer);
            }
        });

        $this->assertSame(['Handled Same message', 'Handled Same message'], $results);
    }

    private function testMessageHandling(int $numWorkers = 1, int $numMessages = 1, int $delayPublisher = 0, int $delayWorker = 0, ?int $timeout = null)
    {
        /** @var array<Process> $processes */
        $processes = [];

        $expectedResults = [];

        $eventBusSetup = new EventbusSetup();

        for ($i = 0; $i < $numMessages; $i++) {
            $message = uniqid('Message_');
            $expectedResults[] = 'Handled '.$message;

            $this->runPublisher($message, $delayPublisher);
        }

        sort($expectedResults);
        for ($i = 0; $i < $numWorkers; $i++) {
            $processes[] = $this->runWorker(null, $delayWorker);
        }

        do {
            // wait for the given time
            sleep(1);

            // remove all finished processes from the stack
            foreach ($processes as $index => $process) {
                if (!$process->isRunning()) {
                    foreach (explode("\r\n",$process->getOutput()) as $line) {
                        if (empty($line)) {
                            continue;
                        }
                        $eventBusSetup->getMessageResultStore()->addResult($line);
                    }
                    unset($processes[$index]);
                }
            }
        } while (count($processes) > 0);


        $results = iterator_to_array($eventBusSetup->getMessageResultStore()->getIterator());
        sort($results);
        $this->assertSame($expectedResults, $results);
    }

    private function runWorker(?string $host = null, int $delay = 0): Process
    {
        $host ??= 'nats';

        $process = new Process([__DIR__.'/../../../Fixtures/bin/worker', $host, $delay]);
        $process->start();

        return $process;
    }

    private function runPublisher($message, int $delay = 0, bool $deduplication = false): Process
    {
        $process = new Process([__DIR__.'/../../../Fixtures/bin/publisher', $message, $delay, $deduplication]);
        $process->start();

        return $process;
    }
}
