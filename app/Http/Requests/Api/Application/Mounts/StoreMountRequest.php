<?php

namespace Pterodactyl\Http\Requests\Api\Application\Mounts;

use Pterodactyl\Models\Mount;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreMountRequest extends ApplicationApiRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? Mount::getRules();
    }
}
