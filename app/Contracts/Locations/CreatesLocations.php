<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Locations;

use Pterodactyl\Models\Location;

interface CreatesLocations
{
    /**
     * Create a new location. The input is validated here, so it can come straight from any caller;
     * extension field values go under `extensions` as ValidatedExtensionValues.
     *
     * @param  array<string, mixed>  $input  short and long
     */
    public function create(array $input): Location;
}
