<?php

namespace Etrias\AsyncBundle\Workers;


use Doctrine\Common\Persistence\ManagerRegistry;
use Etrias\AsyncBundle\Event\BackgroundJobHandledEvent;
use Etrias\AsyncBundle\Registry\JobRegistry;
use GearmanJob;
use League\Tactician\CommandBus;
use Mmoreram\GearmanBundle\Command\Util\GearmanOutputAwareInterface;
use Mmoreram\GearmanBundle\Service\GearmanClient;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\SerializerInterface;

class CommandBusWorker implements GearmanOutputAwareInterface
{
    /**
     * @var GearmanClient
     */
    protected $gearmanClient;
    /**
     * @var SerializerInterface
     */
    protected $serializer;
    /**
     * @var string
     */
    protected $encoding;
    /**
     * @var CommandBus
     */
    protected $commandBus;

    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var OutputInterface
     */
    protected $output;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var JobRegistry
     */
    protected $jobRegistry;
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * CommandBusWorker constructor.
     * @param GearmanClient $gearmanClient
     * @param SerializerInterface $serializer
     * @param string $encoding
     * @param CommandBus $commandBus
     * @param EventDispatcherInterface $dispatcher
     * @param JobRegistry $jobRegistry
     * @param LoggerInterface $logger
     * @param ManagerRegistry|null $doctrine
     */
    public function __construct(
        GearmanClient $gearmanClient,
        SerializerInterface $serializer,
        string $encoding,
        CommandBus $commandBus,
        EventDispatcherInterface $dispatcher,
        JobRegistry $jobRegistry,
        LoggerInterface $logger,
        ManagerRegistry $doctrine = null
    )
    {
        $this->gearmanClient = $gearmanClient;
        $this->serializer = $serializer;

        if (!$serializer->supportsDecoding($encoding)) {
            throw new UnsupportedException('Not supported encoding: '. $encoding);
        }
        $this->encoding = $encoding;
        $this->commandBus = $commandBus;
        $this->dispatcher = $dispatcher;

        $this->output = new NullOutput();
        $this->logger = $logger;
        $this->jobRegistry = $jobRegistry;
        $this->doctrine = $doctrine;

        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function handle(GearmanJob $job, $context)
    {
        try {
            $this->output->writeln(sprintf('<info>Start handling job</info> %s <comment>%s</comment>', $job->functionName(), $job->handle()));
            $workload = $this->serializer->decode($job->workload(), $this->encoding);

            $workload['command'] = $this->mergeEntities($workload['command']);
            $result = $this->commandBus->handle($workload['command']);

            $this->output->writeln(sprintf('<info>Finish handling job</info> %s <comment>%s</comment>', $job->functionName(), $job->handle()));


            if ($this->isBackgroundJob($context)) {
                $event = new BackgroundJobHandledEvent($job->handle(), $workload, $result);
                $this->dispatcher->dispatch(BackgroundJobHandledEvent::NAME, $event);

            } else {

                return $result;
            }

        } catch (\Error | \Exception $exception) {
            $this->output->writeln(sprintf('<error>Error handling job</error> %s <comment>%s</comment>', $job->functionName(), $job->handle()));
            if ($this->output->isVeryVerbose()) {
                $this->output->write(sprintf('%s (%s)', $exception->getMessage(), get_class($exception)));
            }

            $this->logger->critical($exception);


            $job->sendException($exception->getMessage());

            if ($this->isBackgroundJob($context)) {
                $this->gearmanClient->doBackgroundJob($this->getJobNameWithoutPrefix($job->functionName(), $context), $job->workload());
            }

            exit(1);
        }
    }

    /**
     * @param array $context
     * @return bool
     */
    protected function isBackgroundJob(array $context)
    {
        $jobConfig = $context['jobs'][0];

        $method = $jobConfig['defaultMethod'];

        return strpos(strtolower($method), 'background') !== false;
    }

    protected function getJobNameWithoutPrefix(string $functionName, array $context) {
        $jobConfigs = array_filter($context['jobs'], function($jobConfig) use ($functionName) {
            return $jobConfig['realCallableName'] === $functionName;
        });

        $jobConfig = reset($jobConfigs);

        return $jobConfig['realCallableNameNoPrefix'];

    }

    /**
     * Set the output
     *
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param object $command
     * @return object
     * @throws \ReflectionException
     */
    protected function mergeEntities(object $command)
    {
        if (!$this->doctrine) {
            return $command;
        }

        $commandReflection = new ReflectionClass($command);

        foreach ($commandReflection->getProperties() as $property) {
            $value = $this->propertyAccessor->getValue($command, $property->getName());

            if (!is_object($value)) {
                continue;
            }

            $className = get_class($value);
            if ($entityManager = $this->doctrine->getManagerForClass($className)) {

                $identifierValues = $entityManager->getClassMetadata($className)->getIdentifierValues($value);
                $this->propertyAccessor->setValue($command, $property->getName(), $entityManager->getRepository($className)->find($identifierValues));
            }
        }

        return $command;
    }
}
