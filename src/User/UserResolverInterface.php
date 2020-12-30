<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\User;

use Etrias\AsyncBundle\Authentication\Token\CommandBusToken;
use Etrias\AsyncBundle\Command\UserAwareCommandWrapper;

interface UserResolverInterface
{
    /**
     * @param string|object $user
     *
     * @see UserAwareCommandWrapper::setUserId()
     */
    public function toUserId($user);

    /**
     * @see CommandBusToken::setUser()
     *
     * @param mixed $userId
     *
     * @return string|object
     */
    public function fromUserId($userId);
}
