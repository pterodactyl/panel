<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Resources\Api\Client;

use Illuminate\Http\Request;
use Pterodactyl\Data\ServerState;

/**
 * @extends ClientResource<ServerState>
 */
class StatsResource extends ClientResource
{
    public function resourceName(): string
    {
        return 'stats';
    }

    public function toArray(Request $request): array
    {
        return [
            'current_state' => $this->resource->state,
            'is_suspended' => $this->resource->isSuspended,
            'resources' => [
                'memory_bytes' => $this->resource->memoryBytes,
                'cpu_absolute' => $this->resource->cpuAbsolute,
                'disk_bytes' => $this->resource->diskBytes,
                'network_rx_bytes' => $this->resource->networkRxBytes,
                'network_tx_bytes' => $this->resource->networkTxBytes,
                'uptime' => $this->resource->uptime,
            ],
        ];
    }
}
