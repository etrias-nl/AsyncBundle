<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Middleware;

use Etrias\Bundles\CMSUserBundle\Repository\UserRepository;
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
     * @var UserRepository
     */
    protected $userRepository;

    public function __construct(
        UserRepository $userRepository,
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
        if ($command instanceof UserAwareCommandWrapper) {
            $user = $this->userRepository->getReference($command->getUserId());
            $command = $command->getCommand();

            $token = new CommandBusToken([]);
            $token->setUser($user);

            $this->tokenStorage->setToken($token);
        }

        $result = $next($command);

        if ($command instanceof UserAwareCommandWrapper) {
            $this->tokenStorage->setToken(null);
        }

        return $result;
    }
}
