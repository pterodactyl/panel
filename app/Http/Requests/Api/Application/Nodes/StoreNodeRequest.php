<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Support\JsonValueGuard;
use Pterodactyl\Support\ValidationRuleSubset;
use Pterodactyl\Validation\NodeRules;

class StoreNodeRequest extends ApplicationApiRequest
{
    protected ?string $resource = AdminAcl::RESOURCE_NODES;

    protected int $permission = AdminAcl::WRITE;

    /**
     * Validation rules to apply to this request.
     *
     * @param  NormalizedValidationRules|null  $rules
     * @return ValidationRules
     */
    public function rules(?array $rules = null): array
    {
        $selected = ValidationRuleSubset::select($rules ?? NodeRules::rules(), [
            'public',
            'name',
            'description',
            'location_id',
            'fqdn',
            'scheme',
            'behind_proxy',
            'maintenance_mode',
            'memory',
            'memory_overallocate',
            'disk',
            'disk_overallocate',
            'upload_size',
            'daemonListen',
            'daemonSFTP',
            'daemonBase',
        ]);

        $normalized = [];
        foreach ($selected as $key => $value) {
            $key = ($key === 'daemonSFTP') ? 'daemonSftp' : $key;
            $normalized[Str::snake($key)] = $value;
        }

        return $normalized;
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
     *
     * @return NodeCreationData
     */
    public function payload(): array
    {
        $data = parent::validated();

        $daemonBase = Arr::get($data, 'daemon_base');

        $response = [
            'name' => JsonValueGuard::string(Arr::get($data, 'name')),
            'location_id' => JsonValueGuard::integer(Arr::get($data, 'location_id')),
            'fqdn' => JsonValueGuard::string(Arr::get($data, 'fqdn')),
            'scheme' => JsonValueGuard::string(Arr::get($data, 'scheme')),
            'memory' => JsonValueGuard::integer(Arr::get($data, 'memory')),
            'memory_overallocate' => JsonValueGuard::integer(Arr::get($data, 'memory_overallocate')),
            'disk' => JsonValueGuard::integer(Arr::get($data, 'disk')),
            'disk_overallocate' => JsonValueGuard::integer(Arr::get($data, 'disk_overallocate')),
            'daemonListen' => JsonValueGuard::integer(Arr::get($data, 'daemon_listen')),
            'daemonSFTP' => JsonValueGuard::integer(Arr::get($data, 'daemon_sftp')),
            'daemonBase' => $daemonBase !== null && $daemonBase !== '' ? JsonValueGuard::string($daemonBase) : '/var/lib/pterodactyl/volumes',
        ];

        if (array_key_exists('description', $data)) {
            $description = Arr::get($data, 'description');
            $response['description'] = $description !== null && $description !== '' ? JsonValueGuard::string($description) : null;
        }

        foreach (['public', 'behind_proxy', 'maintenance_mode'] as $boolean) {
            if (array_key_exists($boolean, $data)) {
                $response[$boolean] = JsonValueGuard::boolean(Arr::get($data, $boolean));
            }
        }

        $uploadSize = Arr::get($data, 'upload_size');
        if ($uploadSize !== null && $uploadSize !== '') {
            $response['upload_size'] = JsonValueGuard::integer($uploadSize);
        }

        return $response;
    }
}
