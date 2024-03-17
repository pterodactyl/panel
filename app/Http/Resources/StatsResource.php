<?php

namespace Pterodactyl\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class StatsResource extends JsonResource
{
    public bool $preserveKeys = true;

    public function toArray($request): array
    {
        return [
            'current_state' => $this['state'] ?? 'stopped',
            'is_suspended' => $this['is_suspended'] ?? false,
            'resources' => [
                'memory_bytes' => $this['utilization.memory_bytes'] ?? 0,
                'cpu_absolute' => $this['utilization.cpu_absolute'] ?? 0,
                'disk_bytes' => $this['utilization.disk_bytes'] ?? 0,
                'network_rx_bytes' => $this['utilization.network.rx_bytes'] ?? 0,
                'network_tx_bytes' => $this['utilization.network.tx_bytes'] ?? 0,
                'uptime' => $this['utilization.uptime'] ?? 0,
            ],
        ];
    }
}
