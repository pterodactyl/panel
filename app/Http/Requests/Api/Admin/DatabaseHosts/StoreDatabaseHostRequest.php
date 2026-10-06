<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\DatabaseHosts;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Validation\DatabaseHostRules;

class StoreDatabaseHostRequest extends AdminApiRequest
{
    use ValidatesExtensionFields;

    public function permissions(): array
    {
        return [Permissions::AdminDatabaseHostsCreate];
    }

    /**
     * {@inheritdoc}
     */
    public function extensionForm(): string
    {
        return 'admin.databaseHost';
    }

    /**
     * Validation rules for creating or updating a database host.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return DatabaseHostRules::rules();
    }

    /** @return DatabaseHostUpdateData */
    public function payload(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'host' => $this->string('host')->toString(),
            'port' => $this->integer('port'),
            'username' => $this->string('username')->toString(),
            'password' => $this->filled('password') ? $this->string('password')->toString() : null,
            'node_id' => $this->filled('node_id') ? $this->integer('node_id') : null,
        ];
    }

    /** Default an unsupplied node_id to null so the host is treated as global. */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('node_id')) {
            $this->merge(['node_id' => null]);
        }
    }
}
