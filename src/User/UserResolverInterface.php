<?php

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
     * @return string|object
     *
     * @see CommandBusToken::setUser()
     */
    public function fromUserId($userId);
}
