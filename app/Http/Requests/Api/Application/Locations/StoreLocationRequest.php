<?php

namespace Pterodactyl\Http\Requests\Api\Application\Locations;

use Pterodactyl\Models\Location;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreLocationRequest extends ApplicationApiRequest
{
    public function rules(): array
    {
        return collect(Location::getRules())->only([
            'long',
            'short',
        ])->toArray();
    }

    public function attributes(): array
    {
        return [
            'long' => 'Location Description',
            'short' => 'Location Identifier',
        ];
    }
}
