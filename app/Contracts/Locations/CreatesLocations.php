<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Locations;

use Pterodactyl\Models\Location;

interface CreatesLocations
{
    /**
     * Create a new location from validated attributes.
     *
     * @param  LocationCreationData  $data
     */
    public function create(array $data): Location;
}
