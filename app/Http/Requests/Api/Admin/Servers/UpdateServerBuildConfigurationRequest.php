<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Pterodactyl\Actions\Servers\UpdateServerBuild;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Server;

class UpdateServerBuildConfigurationRequest extends ServerWriteRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminServersUpdate];
    }

    /**
     * Validation rules for updating a server's build configuration.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = UpdateServerBuild::rules($this->parameter('server', Server::class));

        return [
            'allocation_id' => $rules['allocation_id'],
            'oom_disabled' => $rules['oom_disabled'],

            'memory' => $rules['memory'],
            'swap' => $rules['swap'],
            'io' => $rules['io'],
            'cpu' => $rules['cpu'],
            'threads' => $rules['threads'],
            'disk' => $rules['disk'],

            'add_allocation_ids' => $rules['add_allocations'],
            'add_allocation_ids.*' => $rules['add_allocations.*'],
            'remove_allocation_ids' => $rules['remove_allocations'],
            'remove_allocation_ids.*' => $rules['remove_allocations.*'],

            'database_limit' => $rules['database_limit'],
            'allocation_limit' => $rules['allocation_limit'],
            'backup_limit' => $rules['backup_limit'],
        ];
    }

    /**
     * The validated input under the action's field names: add_allocation_ids and
     * remove_allocation_ids are its add_allocations and remove_allocations.
     *
     * @return ActionInput
     */
    public function actionInput(): array
    {
        $input = parent::actionInput();
        foreach (['add_allocation_ids' => 'add_allocations', 'remove_allocation_ids' => 'remove_allocations'] as $field => $key) {
            if (array_key_exists($field, $input)) {
                $input[$key] = $input[$field];
                unset($input[$field]);
            }
        }

        return $input;
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'add_allocation_ids' => 'allocations to add',
            'remove_allocation_ids' => 'allocations to remove',
            'add_allocation_ids.*' => 'allocation to add',
            'remove_allocation_ids.*' => 'allocation to remove',
            'database_limit' => 'Database Limit',
            'allocation_limit' => 'Allocation Limit',
            'backup_limit' => 'Backup Limit',
        ];
    }
}
