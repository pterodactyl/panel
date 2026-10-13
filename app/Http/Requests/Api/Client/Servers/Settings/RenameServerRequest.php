<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Settings;

use Pterodactyl\Actions\Servers\UpdateServerDetails;
use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;

class RenameServerRequest extends ClientApiRequest implements ClientPermissionsRequest
{
    /**
     * Returns the permissions string indicating which permission should be used to
     * validate that the authenticated user has permission to perform this action against
     * the given resource (server).
     */
    public function permission(): string
    {
        return Permissions::SettingsRename->value;
    }

    /**
     * The rules to apply when validating this request.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        $rules = UpdateServerDetails::rules();

        return [
            'name' => $rules['name'],
            'description' => $rules['description'],
        ];
    }
}
