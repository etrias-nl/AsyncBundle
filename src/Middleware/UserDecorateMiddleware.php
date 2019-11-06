<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Middleware;

use Etrias\Bundles\CMSUserBundle\Repository\UserRepository;
use Etrias\AsyncBundle\Authentication\Token\CommandBusToken;
use Etrias\AsyncBundle\Command\UserAwareCommandWrapper;
use Etrias\Bundles\CoreCQRSBundle\Command\CommandInterface;
use League\Tactician\Middleware;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserDecorateMiddleware implements Middleware
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
     * @param CommandInterface $command
     * @param callable $next
     *
     * @return mixed
     * @throws \Doctrine\ORM\ORMException
     */
    public function execute($command, callable $next)
    {
        if ($token = $this->tokenStorage->getToken()) {
            if ($user = $token->getUser()) {
                $command = new UserAwareCommandWrapper($command, $this->tokenStorage->getToken()->getUser()->getId());
            }
        }

        return $next($command);
    }
}
