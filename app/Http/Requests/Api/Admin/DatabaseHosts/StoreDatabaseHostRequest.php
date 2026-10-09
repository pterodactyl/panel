<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\DatabaseHosts;

use Illuminate\Database\Eloquent\Model;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\DatabaseHost;
use Pterodactyl\Validation\DatabaseHostRules;

class StoreDatabaseHostRequest extends AdminApiRequest
{
    use ValidatesExtensionFields;

    public function permissions(): array
    {
        return [Permissions::AdminDatabaseHostsCreate];
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
            'extensions' => $this->extensionValues(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Model|string
    {
        return DatabaseHost::class;
    }

    /** Default an unsupplied node_id to null so the host is treated as global. */
    protected function prepareForValidation(): void
    {
        if (! $this->filled('node_id')) {
            $this->merge(['node_id' => null]);
        }
    }
}
