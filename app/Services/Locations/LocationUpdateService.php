<?php

namespace Pterodactyl\Services\Locations;

use Pterodactyl\Models\Location;

class LocationUpdateService
{
    /**
     * Update an existing location.
     *
     */
    public function handle(Location|int $location, array $data): Location
    {
        $location = ($location instanceof Location) ? $location->id : $location;

        return $location->update($data);
    }
}
