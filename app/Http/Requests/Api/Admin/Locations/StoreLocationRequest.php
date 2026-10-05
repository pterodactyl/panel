<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Locations;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Http\Requests\Api\Admin\AdminApiRequest;
use Pterodactyl\Validation\LocationRules;

class StoreLocationRequest extends AdminApiRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminLocationsCreate];
    }

    /**
     * Validation rules for creating a location.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return LocationRules::rules();
    }

    /** Rename fields to be more clear in error messages. */
    public function attributes(): array
    {
        return [
            'short' => 'Location Identifier',
            'long' => 'Location Description',
        ];
    }

    /** @return LocationCreationData */
    public function payload(): array
    {
        return [
            'short' => $this->string('short')->toString(),
            'long' => $this->filled('long') ? $this->string('long')->toString() : null,
        ];
    }
}
