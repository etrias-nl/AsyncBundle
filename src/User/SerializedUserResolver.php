<?php

namespace Etrias\AsyncBundle\User;

class SerializedUserResolver implements UserResolverInterface
{
    /**
     * {@inheritDoc}
     */
    public function toUserId($user)
    {
        return serialize($user);
    }

    /**
     * {@inheritDoc}
     */
    public function fromUserId($userId)
    {
        return unserialize($userId);
    }
}
