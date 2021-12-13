<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\User;

use Etrias\AsyncBundle\Authentication\Token\CommandBusToken;
use Etrias\AsyncBundle\Command\UserAwareCommandWrapper;

interface UserResolverInterface
{
    /**
     * @see UserAwareCommandWrapper::setUserId()
     *
     * @param string|object $user
     *
     * @return int|string|int[]|string[]|null
     */
    public function toUserId($user);

    /**
     * @see CommandBusToken::setUser()
     *
     * @param int|string|int[]|string[] $userId
     *
     * @return string|object|null
     */
    public function fromUserId($userId);
}
