<?php

declare(strict_types=1);

namespace Pterodactyl\Models\Objects;

class DeploymentObject
{
    private bool $dedicated = false;

    /**
     * Location IDs exactly as they arrived from the request; FindViableNodesService
     * is what rejects anything that is not an integer or an integer-like string.
     *
     * @var list<int|string>
     */
    private array $locations = [];

    /**
     * Individual ports and port ranges exactly as they arrived from the request;
     * AllocationSelectionService discards the entries it cannot use.
     *
     * @var list<ApiScalar>
     */
    private array $ports = [];

    /**
     * The deploy tags (D) this deployment declares. A node's reservations are
     * matched against these exactly - see NodeTagGate.
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
     * @return list<int|string>
     */
    public function getLocations(): array
    {
        return $this->locations;
    }

    /**
     * @param  list<int|string>  $locations
     */
    public function setLocations(array $locations): self
    {
        $this->locations = $locations;

        return $this;
    }

    /**
     * @return list<ApiScalar>
     */
    public function getPorts(): array
    {
        return $this->ports;
    }

    /**
     * @param  list<ApiScalar>  $ports
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
