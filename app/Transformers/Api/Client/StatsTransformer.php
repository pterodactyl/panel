<?php

namespace Pterodactyl\Transformers\Api\Client;

use Illuminate\Support\Arr;
use Pterodactyl\Transformers\Api\Transformer;

class StatsTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return 'stats';
    }

    /**
     * Transform stats from the daemon into a result set that can be used in
     * the client API.
     */
    public function transform(array $model): array
    {
        return [
            'current_state' => Arr::get($model, 'state', 'stopped'),
            'is_suspended' => Arr::get($model, 'is_suspended', false),
            'resources' => [
                'memory_bytes' => Arr::get($model, 'utilization.memory_bytes', 0),
                'cpu_absolute' => Arr::get($model, 'utilization.cpu_absolute', 0),
                'disk_bytes' => Arr::get($model, 'utilization.disk_bytes', 0),
                'network_rx_bytes' => Arr::get($model, 'utilization.network.rx_bytes', 0),
                'network_tx_bytes' => Arr::get($model, 'utilization.network.tx_bytes', 0),
                'uptime' => Arr::get($model, 'utilization.uptime', 0),
            ],
        ];
    }
}
