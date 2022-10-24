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
        /** @var Location $location */
        if (is_int($location)) {
            $location = Location::query()->findOrFail($location);
        }

        $location->update($data);

        return $location;
    }
}
