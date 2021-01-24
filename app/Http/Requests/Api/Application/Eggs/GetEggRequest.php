<?php

namespace Pterodactyl\Http\Requests\Api\Application\Eggs;

use Pterodactyl\Models\Egg;

class GetEggRequest extends GetEggsRequest
{
    public function resourceExists(): bool
    {
        $egg = $this->route()->parameter('egg');

        return $egg instanceof Egg && $egg->exists;
    }
}
