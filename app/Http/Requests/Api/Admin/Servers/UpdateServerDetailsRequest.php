<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\Server;
use Pterodactyl\Validation\ServerRules;

class UpdateServerDetailsRequest extends ServerWriteRequest
{
    use ValidatesExtensionFields;

    public function permissions(): array
    {
        return [Permissions::AdminServersUpdate];
    }

    /**
     * Validation rules for updating a server's details.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = ServerRules::rules($this->parameter('server', Server::class));

        return [
            'external_id' => $rules['external_id'],
            'name' => $rules['name'],
            'owner_id' => $rules['owner_id'],
            'description' => array_merge(['nullable'], $rules['description']),
        ];
    }

    /**
     * Normalize the validated data for the service.
     *
     * @return ServerDetailsModificationData
     */
    public function payload(): array
    {
        return [
            'external_id' => $this->filled('external_id') ? $this->string('external_id')->toString() : null,
            'name' => $this->string('name')->toString(),
            'owner_id' => $this->integer('owner_id'),
            'description' => $this->filled('description') ? $this->string('description')->toString() : null,
            'extensions' => $this->extensionValues(),
        ];
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'owner_id' => 'User ID',
            'name' => 'Server Name',
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Server
    {
        return $this->parameter('server', Server::class);
    }
}
