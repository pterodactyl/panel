<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Data\ServerState;

class StatsTransformer extends BaseClientTransformer
{
    public function getResourceName(): string
    {
        return 'stats';
    }

    /**
     * Transform the live state of a server into a result set that can be used
     * in the client API.
     *
     * @return ApiPayload
     */
    public function transform(ServerState $state): array
    {
        return [
            'current_state' => $state->state,
            'is_suspended' => $state->isSuspended,
            'resources' => [
                'memory_bytes' => $state->memoryBytes,
                'cpu_absolute' => $state->cpuAbsolute,
                'disk_bytes' => $state->diskBytes,
                'network_rx_bytes' => $state->networkRxBytes,
                'network_tx_bytes' => $state->networkTxBytes,
                'uptime' => $state->uptime,
            ],
        ];
    }
}
