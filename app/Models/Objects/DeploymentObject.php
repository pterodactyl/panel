<?php

namespace Pterodactyl\Models\Objects;

class DeploymentObject
{
    /**
     * @var bool
     */
    private $dedicated = false;

    /**
     * @var array
     */
    private $locations = [];

    /**
     * @var array
     */
    private $ports = [];

    /**
     * @var array
     */
    private $additionalPorts = [];

    /**
     * @return bool
     */
    public function isDedicated(): bool
    {
        return $this->dedicated;
    }

    /**
     * @param bool $dedicated
     * @return $this
     */
    public function setDedicated(bool $dedicated)
    {
        $this->dedicated = $dedicated;

        return $this;
    }

    /**
     * @return array
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * @param array $locations
     * @return $this
     */
    public function setLocations(array $locations)
    {
        $this->locations = $locations;

        return $this;
    }

    /**
     * @return array
     */
    public function getPorts(): array
    {
        return $this->ports;
    }

    /**
     * @param array $ports
     * @return $this
     */
    public function setPorts(array $ports)
    {
        $this->ports = $ports;

        return $this;
    }

    /**
     * @return array
     */
    public function getAdditionalPorts(): array
    {
        return $this->additionalPorts;
    }

    /**
     * @param array $ports
     * @return $this
     */
    public function setAdditionalPorts(array $additionalPorts)
    {
        $this->additionalPorts = $additionalPorts;

        return $this;
    }
}
