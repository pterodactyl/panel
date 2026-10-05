<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Locations;

use Pterodactyl\Exceptions\Service\Location\HasActiveNodesException;
use Pterodactyl\Models\Location;

interface DeletesLocations
{
    /**
     * Delete a location, provided no nodes are assigned to it.
     *
     * @throws HasActiveNodesException
     */
    public function delete(Location $location): void;
}
