<?php

namespace Pterodactyl\Services\Locations;

use Pterodactyl\Models\Location;
use Pterodactyl\Exceptions\Service\Location\HasActiveNodesException;

class LocationDeletionService
{
    /**
     * Delete an existing location.
     *
     * @throws HasActiveNodesException
     */
    public function handle(Location|int $location): ?int
    {
        /** @var Location $location */
        $location = ($location instanceof Location) ? $location : Location::query()->findOrFail($location);

        $count = $location->nodes()->count();
        if ($count > 0) {
            throw new HasActiveNodesException(trans('exceptions.locations.has_nodes'));
        }

        return $location->delete();
    }
}
