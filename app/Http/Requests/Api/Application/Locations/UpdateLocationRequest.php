<?php

namespace Pterodactyl\Http\Requests\Api\Application\Locations;

use Pterodactyl\Models\Location;

class UpdateLocationRequest extends StoreLocationRequest
{
    /**
     * Determine if the requested location exists on the Panel.
     */
    public function resourceExists(): bool
    {
        $location = $this->route()->parameter('location');

        return $location instanceof Location && $location->exists;
    }

    /**
     * Rules to validate this request against.
     */
    public function rules(): array
    {
        $locationId = $this->route()->parameter('location')->id;

        return collect(Location::getRulesForUpdate($locationId))->only([
            'short',
            'long',
        ])->toArray();
    }
}
