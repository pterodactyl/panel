<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Servers;

use Pterodactyl\Actions\Servers\UpdateServerStartup;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\User;

class UpdateServerStartupRequest extends ServerWriteRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminServersUpdate];
    }

    /**
     * Validation rules for updating a server's startup configuration.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return UpdateServerStartup::rules(User::USER_LEVEL_ADMIN);
    }
}
