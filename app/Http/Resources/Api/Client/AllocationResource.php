<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Allocation;
use UnexpectedValueException;

/**
 * @extends ClientResource<Allocation>
 */
#[ResponseField('notes', example: 'Primary game port', nullable: true)]
class AllocationResource extends ClientResource
{
    protected array $eagerLoads = ['server'];

    public function resourceName(): string
    {
        return 'allocation';
    }

    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('server');
        $server = $this->resource->server ?? throw new UnexpectedValueException('The allocation does not belong to a server.');

        return [
            'id' => $this->resource->id,
            'ip' => $this->resource->ip,
            'ip_alias' => $this->resource->ip_alias,
            'port' => $this->resource->port,
            'notes' => $this->resource->notes,
            'is_default' => $server->allocation_id === $this->resource->id,
        ];
    }
}
