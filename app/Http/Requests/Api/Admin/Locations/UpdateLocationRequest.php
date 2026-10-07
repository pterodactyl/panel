<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Locations;

use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Location;
use Pterodactyl\Validation\LocationRules;

class UpdateLocationRequest extends StoreLocationRequest
{
    public function permissions(): array
    {
        return [Permissions::AdminLocationsUpdate];
    }

    /**
     * Validation rules for updating a location.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return LocationRules::rules($this->parameter('location', Location::class));
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Location
    {
        return $this->parameter('location', Location::class);
    }
}
