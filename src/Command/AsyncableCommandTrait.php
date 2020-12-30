<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Command;

trait AsyncableCommandTrait
{
    /** @var bool|null */
    protected $async = null;

    public function getAsync(): ?bool
    {
        return $this->async;
    }

    /**
     * @return object
     */
    public function setAsync(?bool $async): self
    {
        $this->async = $async;

        return $this;
    }
}
