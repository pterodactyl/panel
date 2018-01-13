<?php

namespace Pterodactyl\Http\Controllers\API\Admin\Locations;

use Pterodactyl\Models\Location;
use Pterodactyl\Http\Requests\API\Admin\Locations\GetLocationsRequest;

class GetLocationRequest extends GetLocationsRequest
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
}
