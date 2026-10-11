<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Locations;

use Pterodactyl\Models\Location;

interface UpdatesLocations
{
    /**
     * Update an existing location. The input is validated here, so it can come straight from any caller;
     * extension field values go under `extensions` as ValidatedExtensionValues.
     *
     * @param  ActionInput  $input  short and long
     */
    public function update(Location $location, array $input): Location;
}
