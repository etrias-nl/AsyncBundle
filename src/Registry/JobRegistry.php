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
    )
    {
        $this->configs = $configs;
        $this->checkConfigs = $checkConfigs;
    }

    /**
     * @return JobConfig[]|iterable
     */
    public function getAllConfigs() {
        return $this->configs;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasConfig(string $name): bool{
        return isset($this->configs[$name]);
    }

    /**
     * @param string $name
     * @return JobConfig
     */
    public function getConfig(string $name): JobConfig {
        return $this->configs[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasCheckConfig(string $name): bool{
        return isset($this->checkConfigs[$name]);
    }

    /**
     * @param string $name
     * @return array
     */
    public function getCheckConfig(string $name): array {
        return $this->checkConfigs[$name];
    }

    /**
     * @param string $name
     * @param JobConfig $config
     * @param array $checkConfig
     * @return $this
     */
    public function add(string $name, JobConfig $config, array $checkConfig = []) {
        $this->configs[$name] = $config;
        $this->checkConfigs[$name] = $checkConfig;

        return $this;
    }
}
