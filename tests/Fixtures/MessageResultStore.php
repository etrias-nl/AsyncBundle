<?php

namespace Tests\Etrias\AsyncBundle\Fixtures;

use Traversable;

class MessageResultStore implements \IteratorAggregate
{
    private iterable $results = [];

    public function getIterator(): Traversable
    {
        return  new \ArrayIterator($this->results);
    }

    public function addResult($result)
    {
        $this->results[] = $result;
    }
}