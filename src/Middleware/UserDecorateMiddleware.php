<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Middleware;

use Etrias\AsyncBundle\Command\UserAwareCommandWrapper;
use Etrias\CqrsBundle\Command\CommandInterface;
use League\Tactician\Middleware;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserDecorateMiddleware implements Middleware
{
    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    public function __construct(
        TokenStorageInterface $tokenStorage
    )
    {
        $this->tokenStorage = $tokenStorage;
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
        if (!$command instanceof CommandInterface) {
            return $next($command);
        }

        if ($token = $this->tokenStorage->getToken()) {
            if ($user = $token->getUser()) {
                $command = new UserAwareCommandWrapper($command, $this->tokenStorage->getToken()->getUser()->getId());
            }
        }

        return $next($command);
    }
}
