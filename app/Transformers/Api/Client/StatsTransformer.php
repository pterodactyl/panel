<?php

namespace Pterodactyl\Transformers\Api\Client;

use Illuminate\Support\Arr;

class StatsTransformer extends BaseClientTransformer
{
    /**
     * @return string
     */
    public function getResourceName(): string
    {
        return 'stats';
    }

    /**
     * Transform stats from the daemon into a result set that can be used in
     * the client API.
     *
     * @param array $data
     * @return array
     */
    public function transform(array $data)
    {
        return [
            'current_state' => Arr::get($data, 'state', 'stopped'),
            'is_suspended' => Arr::get($data, 'suspended', false),
            'resources' => [
                'memory_bytes' => Arr::get($data, 'resources.memory_bytes', 0),
                'cpu_absolute' => Arr::get($data, 'resources.cpu_absolute', 0),
                'disk_bytes' => Arr::get($data, 'resources.disk_bytes', 0),
                'network_rx_bytes' => Arr::get($data, 'resources.network.rx_bytes', 0),
                'network_tx_bytes' => Arr::get($data, 'resources.network.tx_bytes', 0),
            ],
        ];
    }
}
