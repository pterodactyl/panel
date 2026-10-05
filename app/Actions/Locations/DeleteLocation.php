<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Locations;

use Pterodactyl\Contracts\Locations\DeletesLocations;
use Pterodactyl\Exceptions\Service\Location\HasActiveNodesException;
use Pterodactyl\Models\Location;

final class DeleteLocation implements DeletesLocations
{
    /**
     * Delete a location, provided no nodes are assigned to it.
     *
     * @throws HasActiveNodesException
     */
    public function delete(Location $location): void
    {
        if ($location->nodes()->exists()) {
            throw new HasActiveNodesException(trans('exceptions.locations.has_nodes'));
        }

        $location->delete();
    }
}
