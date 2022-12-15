<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Illuminate\Support\Arr;
use Pterodactyl\Models\Node;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreNodeRequest extends ApplicationApiRequest
{
    /**
     * Validation rules to apply to this request.
     */
    public function rules(array $rules = null): array
    {
        return collect($rules ?? Node::getRules())->only([
            'name',
            'description',
            'location_id',
            'database_host_id',
            'fqdn',
            'scheme',
            'behind_proxy',
            'public',

            'listen_port_http',
            'public_port_http',
            'listen_port_sftp',
            'public_port_sftp',

            'memory',
            'memory_overallocate',
            'disk',
            'disk_overallocate',

            'upload_size',
            'daemon_base',
        ])->mapWithKeys(function ($value, $key) {
            return [snake_case($key) => $value];
        })->toArray();
    }

    /**
     * Fields to rename for clarity in the API response.
     */
    public function attributes(): array
    {
        return [
            'daemon_base' => 'Daemon Base Path',
            'upload_size' => 'File Upload Size Limit',
            'location_id' => 'Location',
            'public' => 'Node Visibility',
        ];
    }

    /**
     * Change the formatting of some data keys in the validated response data
     * to match what the application expects in the services.
     */
    public function validated($key = null, $default = null): array
    {
        $response = parent::validated();
        $response['daemon_base'] = $response['daemon_base'] ?? Node::DEFAULT_DAEMON_BASE;

        if (!is_null($key)) {
            return Arr::get($response, $key, $default);
        }

        return $response;
    }
}
