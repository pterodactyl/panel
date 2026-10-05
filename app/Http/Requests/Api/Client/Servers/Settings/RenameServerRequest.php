<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Client\Servers\Settings;

use Pterodactyl\Contracts\Http\ClientPermissionsRequest;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Client\ClientApiRequest;
use Pterodactyl\Validation\ServerRules;
use UnexpectedValueException;

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
        return [
            'name' => ServerRules::rules()['name'],
            'description' => ['string', 'nullable'],
        ];
    }

    public function description(): ?string
    {
        $description = $this->input('description');
        throw_if($description !== null && ! is_string($description), UnexpectedValueException::class, 'The validated server description must be a string or null.');

        return $description;
    }
}
