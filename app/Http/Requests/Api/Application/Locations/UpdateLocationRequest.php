<?php

namespace Pterodactyl\Http\Requests\Api\Application\Locations;

use Pterodactyl\Models\Location;
use Pterodactyl\Http\Controllers\Api\Application\Locations\StoreLocationRequest;

class UpdateLocationRequest extends StoreLocationRequest
{
    /**
     * Determine if the requested location exists on the Panel.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $location = $this->route()->parameter('location');

        return $location instanceof Location && $location->exists;
    }

    /**
     * Rules to validate this request aganist.
     *
     * @return array
     */
    public function rules(): array
    {
        $locationId = $this->route()->parameter('location')->id;

        return collect(Location::getUpdateRulesForId($locationId))->only([
            'short',
            'long',
        ]);
    }
}
