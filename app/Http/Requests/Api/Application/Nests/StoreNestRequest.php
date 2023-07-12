<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nests;

use Pterodactyl\Models\Nest;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreNestRequest extends ApplicationApiRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? Nest::getRules();
    }
}
