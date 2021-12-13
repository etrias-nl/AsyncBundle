<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Middleware;

use Etrias\AsyncBundle\Command\UserAwareCommandWrapper;
use Etrias\AsyncBundle\User\UserResolverInterface;
use Etrias\CqrsBundle\Command\CommandInterface;
use League\Tactician\Middleware;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserDecorateMiddleware implements Middleware
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var UserResolverInterface|null
     */
    protected $userResolver;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        UserResolverInterface $userResolver = null
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->userResolver = $userResolver;
    }

    /**
     * @param object $command
     *
     * @throws \Doctrine\ORM\ORMException
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {
        if (!$command instanceof CommandInterface) {
            return $next($command);
        }

        $token = $this->tokenStorage->getToken();

        if ($token && $user = $token->getUser()) {
            if (null !== $this->userResolver) {
                $userId = $this->userResolver->toUserId($user);
            } elseif (method_exists($user, 'getId')) {
                @trigger_error('Implicitly using "'.\get_class($user).'::getId()" is deprecated, provide a "UserResolverInterface" service instead.', E_USER_DEPRECATED);
                $userId = $user->getId();
            } else {
                throw new \Exception('Unable to obtain user ID from "'.\get_class($user).'" without a "UserResolverInterface" service.');
            }

            if (null !== $userId) {
                $command = new UserAwareCommandWrapper($command, $userId);
            }
        }

        return $next($command);
    }
}
