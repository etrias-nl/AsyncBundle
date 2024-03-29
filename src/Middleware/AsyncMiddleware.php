<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Middleware;

use Etrias\AsyncBundle\Command\AsyncableCommandInterface;
use Etrias\AsyncBundle\Command\WrappedCommandInterface;
use Etrias\AsyncBundle\Event\BackgroundJobQueuedEvent;
use Etrias\AsyncBundle\Exceptions\JobNotFoundException;
use Etrias\AsyncBundle\Logger\ProfileLogger;
use Etrias\AsyncBundle\Registry\JobRegistry;
use League\Tactician\Middleware;
use Mmoreram\GearmanBundle\Service\GearmanClient;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\Exception\UnsupportedException;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Serializer\SerializerInterface;

class AsyncMiddleware implements Middleware, SerializerAwareInterface
{
    use SerializerAwareTrait;

    /**
     * @var JobRegistry
     */
    protected $jobRegistry;
    /**
     * @var LoggerInterface
     */
    protected $logger;
    /**
     * @var GearmanClient
     */
    protected $gearmanClient;
    /**
     * @var string
     */
    protected $encoding;
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;
    /**
     * @var string
     */
    protected $workerEnvironment;
    /**
     * @var KernelInterface
     */
    protected $kernel;
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var ProfileLogger
     */
    protected $profileLogger;

    /** @var bool */
    private $isExecuting = false;

    /**
     * AsyncMiddleware constructor.
     */
    public function __construct(
        GearmanClient $gearmanClient,
        string $encoding,
        string $workerEnvironment,
        JobRegistry $jobRegistry,
        EventDispatcherInterface $dispatcher,
        KernelInterface $kernel,
        LoggerInterface $logger,
        TokenStorageInterface $tokenStorage,
        ManagerRegistry $doctrine = null
    ) {
        $this->gearmanClient = $gearmanClient;
        $this->jobRegistry = $jobRegistry;
        $this->logger = $logger;

        $this->encoding = $encoding;
        $this->dispatcher = $dispatcher;
        $this->workerEnvironment = $workerEnvironment;
        $this->kernel = $kernel;
        $this->doctrine = $doctrine;
        $this->tokenStorage = $tokenStorage;
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        if (!$serializer->supportsEncoding($this->encoding)) {
            throw new UnsupportedException('Not supported encoding: '.$encoding);
        }

        $this->serializer = $serializer;
    }

    public function setProfileLogger(ProfileLogger $profileLogger): void
    {
        $this->profileLogger = $profileLogger;
    }

    /**
     * @param object $command
     *
     * @throws JobNotFoundException
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        if ($command instanceof WrappedCommandInterface) {
            $innerCommand = $command->getCommand();
        } else {
            $innerCommand = $command;
        }

        $forceAsync = $innerCommand instanceof AsyncableCommandInterface ? $innerCommand->getAsync() : null;

        if ($this->kernel->getEnvironment() === $this->workerEnvironment) {
            if (!$this->isExecuting && true !== $forceAsync) {
                $this->isExecuting = true;

                $returnValue = $next($command);
                $this->isExecuting = false;

                return $returnValue;
            }
        }

        try {
            $commandClassName = $this->getNameForCommand($innerCommand);
        } catch (\InvalidArgumentException $e) {
            $commandClassName = \get_class($innerCommand);
        }

        if (!$this->jobRegistry->hasConfig($commandClassName)) {
            $this->logger->debug('No async configuration defined for command', ['command' => $commandClassName]);

            return $next($command);
        }

        if (false === $forceAsync) {
            $this->logger->debug('Async is overruled by command', ['command' => $commandClassName]);

            return $next($innerCommand);
        }

        $jobConfig = $this->getJobConfig($commandClassName);
        $jobName = $jobConfig['realCallableNameNoPrefix'];
        $jobMethod = $jobConfig['defaultMethod'].'Job';

        if (true === $forceAsync) {
            $innerCommand->setAsync(false);
        }

        $params = $this->serializer->encode([
            'command' => $command,
        ], $this->encoding);

        if ($this->profileLogger) {
            $this->profileLogger->startCommand($command, $jobMethod, $jobConfig);
        }

        $result = $this->gearmanClient->$jobMethod($jobName, $params);

        if ($this->profileLogger) {
            $this->profileLogger->stopCommand();
        }

        $this->logger->info('Queued command to gearman', [
            'command' => $command,
            'method' => $jobMethod,
            'encoding' => $this->encoding,
        ]);

        if (!$this->isBackgroundJob($jobMethod)) {
            return $this->serializer->decode($result, $this->encoding);
        }

        $event = new BackgroundJobQueuedEvent($command, $jobMethod, $result);
        $this->dispatcher->dispatch($event, BackgroundJobQueuedEvent::NAME);
    }

    protected function getJobConfig(string $commandClassName)
    {
        foreach ($this->gearmanClient->getWorkers() as $worker) {
            foreach ($worker['jobs'] as $job) {
                if ($job['callableName'] === $commandClassName) {
                    return $job;
                }
            }
        }

        throw new JobNotFoundException(sprintf('Job for command "%s" is not defined', $commandClassName));
    }

    protected function getNameForCommand($command)
    {
        $className = \get_class($command);
        foreach ($this->kernel->getBundles() as $name => $bundle) {
            if (0 !== strpos($className, $bundle->getNamespace().'\Command')) {
                continue;
            }

            return $name.':'.basename(str_replace('\\', '/', $className));
        }
        throw new \InvalidArgumentException(sprintf('Unable to find a bundle that defines command "%s".', $className));
    }

    /**
     * @return bool
     */
    protected function isBackgroundJob(string $jobMethod)
    {
        return false !== strpos(strtolower($jobMethod), 'background');
    }

    /**
     * @throws \ReflectionException
     *
     * @return object
     */
    protected function detachCommandProperties(object $command)
    {
        if (!$this->doctrine) {
            return $command;
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $commandReflection = new ReflectionClass($command);

        foreach ($commandReflection->getProperties() as $property) {
            $value = $propertyAccessor->getValue($command, $property->getName());
            $entityManager = $this->doctrine->getManagerForClass(\get_class($value));
            $entityManager->detach($value);

            $property->setValue($value);
        }

        return $command;
    }
}
