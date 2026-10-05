<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Locations;

use Pterodactyl\Contracts\Locations\CreatesLocations;
use Pterodactyl\Models\Location;

final class CreateLocation implements CreatesLocations
{
    /**
     * Create a new location from validated attributes.
     *
     * @param  LocationCreationData  $data
     */
    public function create(array $data): Location
    {
        return Location::query()->create($data);
    }
}
