<?php

namespace Pterodactyl\Http\Requests\Api\Application\Eggs;

use Pterodactyl\Models\Egg;

class GetEggRequest extends GetEggsRequest
{
    /**
     * Determine if the requested egg exists on the Panel.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        $egg = $this->route()->parameter('egg');

        return $egg instanceof Egg && $egg->exists;
    }
}
