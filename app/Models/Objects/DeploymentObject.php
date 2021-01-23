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

    public function isDedicated(): bool
    {
        return $this->dedicated;
    }

    /**
     * @return $this
     */
    public function setDedicated(bool $dedicated)
    {
        $this->dedicated = $dedicated;

        return $this;
    }

    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * @return $this
     */
    public function setLocations(array $locations)
    {
        $this->locations = $locations;

        return $this;
    }

    public function getPorts(): array
    {
        return $this->ports;
    }

    /**
     * @return $this
     */
    public function setPorts(array $ports)
    {
        $this->ports = $ports;

        return $this;
    }
}
