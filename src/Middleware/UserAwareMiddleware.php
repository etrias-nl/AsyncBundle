<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Middleware;

use Doctrine\ORM\EntityRepository;
use Etrias\AsyncBundle\Authentication\Token\CommandBusToken;
use Etrias\AsyncBundle\Command\UserAwareCommandWrapper;
use League\Tactician\Middleware;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserAwareMiddleware implements Middleware
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var EntityRepository
     */
    protected $userRepository;

    public function __construct(
        EntityRepository $userRepository,
        TokenStorageInterface $tokenStorage
    )
    {
        $this->tokenStorage = $tokenStorage;
        $this->userRepository = $userRepository;
    }

    /**
     * @param object $command
     * @param callable $next
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function execute($command, callable $next)
    {
        $innerCommand = $command;
        if ($command instanceof UserAwareCommandWrapper) {
            $user = $this->userRepository->find($command->getUserId());
            $innerCommand = $command->getCommand();

            $token = new CommandBusToken([]);
            $token->setUser($user);

            $this->tokenStorage->setToken($token);
        }

        $result = $next($innerCommand);

        if ($command instanceof UserAwareCommandWrapper) {
            $this->tokenStorage->setToken(null);
        }

        return $result;
    }
}
