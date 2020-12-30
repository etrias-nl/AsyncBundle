<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Registry;

use Etrias\AsyncBundle\Module\JobConfig;

class JobRegistry
{
    /**
     * @var iterable|JobConfig[]
     */
    protected $configs;

    /**
     * @var iterable|[]
     */
    protected $checkConfigs;

    public function __construct(
        iterable $configs = [],
        iterable $checkConfigs = []
    ) {
        $this->configs = $configs;
        $this->checkConfigs = $checkConfigs;
    }

    /**
     * @return JobConfig[]|iterable
     */
    public function getAllConfigs()
    {
        return $this->configs;
    }

    public function hasConfig(string $name): bool
    {
        return isset($this->configs[$name]);
    }

    public function getConfig(string $name): JobConfig
    {
        return $this->configs[$name];
    }

    public function hasCheckConfig(string $name): bool
    {
        return isset($this->checkConfigs[$name]);
    }

    public function getCheckConfig(string $name): array
    {
        return $this->checkConfigs[$name];
    }

    /**
     * @return $this
     */
    public function add(string $name, JobConfig $config, array $checkConfig = [])
    {
        $this->configs[$name] = $config;
        $this->checkConfigs[$name] = $checkConfig;

        return $this;
    }
}
