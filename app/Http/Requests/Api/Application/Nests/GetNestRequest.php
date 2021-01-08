<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nests;

use Pterodactyl\Models\Nest;

class GetNestRequest extends GetNestsRequest
{
    /**
     * Determine if the requested nest exists on the Panel.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $nest = $this->route()->parameter('nest');

        return $nest instanceof Nest && $nest->exists;
    }
}
