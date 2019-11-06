<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Middleware;


use Doctrine\Common\Persistence\ManagerRegistry;
use League\Tactician\Middleware;

class DoctrineMiddleware implements Middleware
{
    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param object $command
     * @param callable $next
     *
     * @return mixed
     */
    public function execute($command, callable $next)
    {

        $result = $next($command);

        foreach ($this->doctrine->getManagers() as $name => $manager) {
            if(!$manager->isOpen()) {
                $this->doctrine->resetManager($name);
            }
        }

        return $result;
    }
}
