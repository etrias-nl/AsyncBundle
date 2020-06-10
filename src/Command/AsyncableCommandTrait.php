<?php

declare(strict_types=1);


namespace Etrias\AsyncBundle\Command;


trait AsyncableCommandTrait
{
    /** @var bool|null */
    protected $async = null;

    /**
     * @return bool|null
     */
    public function getAsync(): ?bool
    {
        return $this->async;
    }

    /**
     * @param bool|null $async
     * @return object
     */
    public function setAsync(?bool $async): self
    {
        $this->async = $async;

        return $this;
    }


}
