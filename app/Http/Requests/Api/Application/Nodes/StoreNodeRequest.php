<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Pterodactyl\Models\Node;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreNodeRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_NODES;
    protected int $permission = AdminAcl::WRITE;

    /**
     * Validation rules to apply to this request.
     */
    public function rules(array $rules = null): array
    {
        return collect($rules ?? Node::getRules())->only([
            'public',
            'name',
            'location_id',
            'fqdn',
            'listen_port_http',
            'listen_port_sftp',
            'public_port_http',
            'public_port_sftp',
            'scheme',
            'behind_proxy',
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
     *
     * @return array
     */
    public function attributes()
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
     *
     * @return array
     */
    public function validated()
    {
        $response = parent::validated();
        $response['daemon_base'] = $response['daemon_base'] ?? (new Node())->getAttribute('daemon_base');

        return $response;
    }
}
