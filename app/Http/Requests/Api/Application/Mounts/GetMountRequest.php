<?php

namespace Pterodactyl\Http\Requests\Api\Application\Mounts;

use Pterodactyl\Models\Mount;

class GetMountRequest extends GetMountsRequest
{
    public function resourceExists(): bool
    {
        $mount = $this->route()->parameter('mount');

        return $mount instanceof Mount && $mount->exists;
    }
}
