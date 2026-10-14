<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Pterodactyl\Actions\Servers\UpdateServerDetails;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Concerns\ValidatesExtensionFields;
use Pterodactyl\Models\Server;

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
        return UpdateServerDetails::rules($this->parameter('server', Server::class));
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
