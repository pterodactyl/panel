<?php

namespace Pterodactyl\Services\Locations;

use Pterodactyl\Models\Location;

class LocationCreationService
{
    /**
     * Create a new location.
     *
     */
    public function handle(array $data): Location
    {
        /** @var Location $location */
        $location = Location::query()->create($data);

        return $location;
    }
}
