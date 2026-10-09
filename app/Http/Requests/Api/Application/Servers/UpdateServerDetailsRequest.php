<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Servers;

use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\Server;
use Pterodactyl\Validation\ServerRules;

class UpdateServerDetailsRequest extends ServerWriteRequest
{
    use ValidatesExtensionFields;

    /**
     * Rules to apply to a server details update request.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = ServerRules::rules($this->parameter('server', Server::class));

        return [
            'external_id' => $rules['external_id'],
            'name' => $rules['name'],
            'user' => $rules['owner_id'],
            'description' => array_merge(['nullable'], $rules['description']),
        ];
    }

    /**
     * Convert the posted data into the correct format that is expected
     * by the application.
     *
     * @return ServerDetailsModificationData
     */
    public function payload(): array
    {
        return [
            'external_id' => $this->filled('external_id') ? $this->string('external_id')->toString() : null,
            'name' => $this->string('name')->toString(),
            'owner_id' => $this->integer('user'),
            'description' => $this->filled('description') ? $this->string('description')->toString() : null,
            'extensions' => $this->extensionValues(),
        ];
    }

    /**
     * Rename some attributes in error messages to clarify the field
     * being discussed.
     */
    public function attributes(): array
    {
        return [
            'user' => 'User ID',
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
