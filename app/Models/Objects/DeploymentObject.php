<?php

declare(strict_types=1);

namespace Pterodactyl\Models\Objects;

class DeploymentObject
{
    private bool $dedicated = false;

    /** @var list<int> */
    private array $locations = [];

    /**
     * Individual ports and "start-end" port ranges; entries that are neither are ignored.
     *
     * @var list<int|string>
     */
    private array $ports = [];

    /**
     * The deploy tags this deployment declares. A node's reservations are matched
     * against these exactly (see Node::forDeployTags()).
     *
     * @var array<string>
     */
    private array $tags = [];

    public function isDedicated(): bool
    {
        return $this->dedicated;
    }

    public function setDedicated(bool $dedicated): self
    {
        $this->dedicated = $dedicated;

        return $this;
    }

    /**
     * @return list<int>
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * @param  list<int>  $locations
     */
    public function setLocations(array $locations): self
    {
        $this->locations = $locations;

        return $this;
    }

    /**
     * @return list<int|string>
     */
    public function getPorts(): array
    {
        return $this->ports;
    }

    /**
     * @param  list<int|string>  $ports
     */
    public function setPorts(array $ports): self
    {
        $this->ports = $ports;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * @param  array<string>  $tags
     */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;

        return $this;
    }
}
