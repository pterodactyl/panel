<?php

namespace Pterodactyl\Http\Requests\Api\Application\Eggs;

use Pterodactyl\Models\Egg;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class DeleteEggRequest extends ApplicationApiRequest
{
    public function resourceExists(): bool
    {
        $egg = $this->route()->parameter('egg');

        return $egg instanceof Egg && $egg->exists;
    }
}
