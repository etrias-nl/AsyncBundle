<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Middleware;

use Doctrine\ORM\EntityRepository;
use Etrias\AsyncBundle\Authentication\Token\CommandBusToken;
use Etrias\AsyncBundle\Command\UserAwareCommandWrapper;
use Etrias\AsyncBundle\User\UserResolverInterface;
use League\Tactician\Middleware;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserAwareMiddleware implements Middleware
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var EntityRepository|null
     */
    protected $userRepository;

    /**
     * @var UserResolverInterface|null
     */
    protected $userResolver;

    public function __construct(
        ?EntityRepository $userRepository,
        TokenStorageInterface $tokenStorage,
        UserResolverInterface $userResolver = null
    )
    {
        $this->userRepository = $userRepository;
        $this->tokenStorage = $tokenStorage;
        $this->userResolver = $userResolver;
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
            $userId = $command->getUserId();
            if ($this->userResolver !== null) {
                $user = $this->userResolver->fromUserId($userId);
            } else {
                if ($this->userRepository === null) {
                    throw new \Exception('Resolving users without a "UserResolverInterface" requires an entity repository.');
                }

                @trigger_error('Resolving users using a built-in EntityRepository is deprecated, provide a "UserResolverInterface" service instead.', E_USER_DEPRECATED);
                if (\is_array($userId)) {
                    $user = $this->userRepository->findOneBy($userId);
                } else {
                    $user = $this->userRepository->find($userId);
                }
            }

            $innerCommand = $command->getCommand();
            if ($user) {
                $token = new CommandBusToken([]);
                $token->setUser($user);

                $this->tokenStorage->setToken($token);
            }
        }

        $result = $next($innerCommand);

        if ($command instanceof UserAwareCommandWrapper) {
            $this->tokenStorage->setToken(null);
        }

        return $result;
    }
}
