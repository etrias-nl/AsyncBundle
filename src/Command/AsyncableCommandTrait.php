<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Command;


trait AsyncableCommandTrait
{
    /** @var bool */
    protected $async = true;

    /**
     * @return bool
     */
    public function isAsync(): bool
    {
        return $this->async;
    }

    /**
     * @param bool $async
     * @return object
     */
    public function setAsync(bool $async): self
    {
        $this->async = $async;

        return $this;
    }


}
