<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Locations;

use Pterodactyl\Contracts\Locations\UpdatesLocations;
use Pterodactyl\Models\Location;

final class UpdateLocation implements UpdatesLocations
{
    /**
     * Update an existing location from validated attributes.
     *
     * @param  LocationUpdateData  $data
     */
    public function update(Location $location, array $data): Location
    {
        $location->update($data);

        return $location;
    }
}
