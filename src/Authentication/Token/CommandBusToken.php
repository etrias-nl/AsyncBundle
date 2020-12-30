<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Authentication\Token;

use BadMethodCallException;
use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;

class CommandBusToken extends AbstractToken
{
    /**
     * Returns the user credentials.
     *
     * @return mixed The user credentials
     */
    public function getCredentials()
    {
        throw new BadMethodCallException('You cannot call credentials on the queue');
    }
}
