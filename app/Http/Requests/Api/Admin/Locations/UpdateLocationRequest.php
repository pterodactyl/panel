<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Admin\Locations;

use Pterodactyl\Actions\Locations\CreateLocation;
use Pterodactyl\Enum\Permissions;
use Pterodactyl\Models\Location;

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
        return CreateLocation::rules($this->parameter('location', Location::class));
    }

    /**
     * {@inheritdoc}
     */
    protected function extensionFieldsModel(): Location
    {
        return $this->parameter('location', Location::class);
    }
}
