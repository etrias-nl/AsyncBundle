<?php

declare(strict_types=1);

namespace Etrias\AsyncBundle\Service;

use Net_Gearman_Manager;

class GearmanManager
{
    /** @var Net_Gearman_Manager[] */
    protected $managers = [];

    public function __construct(array $gearmanServers)
    {
        foreach ($gearmanServers as $name => $server) {
            $this->managers[$name] = new Net_Gearman_Manager($server['host'].':'.$server['port']);
        }
    }

    /**
     * @throws \Net_Gearman_Exception
     *
     * @return array
     */
    public function getStatus()
    {
        $result = [];

        foreach ($this->managers as $name => $manager) {
            $result[$name] = $manager->status();
        }

        return $result;
    }

    /**
     * @throws \Net_Gearman_Exception
     *
     * @return array
     */
    public function getWorkers()
    {
        $result = [];

        foreach ($this->managers as $name => $manager) {
            $result[$name] = $manager->workers();
        }

        return $result;
    }

    /**
     * @throws \Net_Gearman_Exception
     *
     * @return array
     */
    public function getVersion()
    {
        $result = [];

        foreach ($this->managers as $name => $manager) {
            $result[$name] = $manager->version();
        }

        return $result;
    }
}
