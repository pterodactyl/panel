<?php

namespace Pterodactyl\Http\Requests\Api\Application\Mounts;

use Pterodactyl\Models\Mount;

class GetMountRequest extends GetMountsRequest
{
    /**
     * Determine if the requested mount exists on the Panel.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $mount = $this->route()->parameter('mount');

        return $mount instanceof Mount && $mount->exists;
    }
}
