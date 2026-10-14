<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Locations;

use Pterodactyl\Models\Location;

interface CreatesLocations
{
    /**
     * The input is validated here. Extension field values go under `extensions` as
     * ValidatedExtensionValues.
     *
     * @param  array<string, mixed>  $input  short and long
     */
    public function create(array $input): Location;
}
