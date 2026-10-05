<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Locations;

use Pterodactyl\Models\Location;

interface UpdatesLocations
{
    /**
     * Update an existing location from validated attributes.
     *
     * @param  LocationUpdateData  $data
     */
    public function update(Location $location, array $data): Location;
}
