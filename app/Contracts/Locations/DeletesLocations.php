<?php

declare(strict_types=1);

namespace Pterodactyl\Contracts\Locations;

use Pterodactyl\Exceptions\Service\Location\HasActiveNodesException;
use Pterodactyl\Models\Location;

interface DeletesLocations
{
    /**
     * A location with nodes assigned to it is not deleted.
     *
     * @throws HasActiveNodesException
     */
    public function delete(Location $location): void;
}
