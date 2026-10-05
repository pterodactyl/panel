<?php

declare(strict_types=1);

namespace Pterodactyl\Http\Requests\Api\Application\Locations;

use Pterodactyl\Models\Location;
use Pterodactyl\Validation\LocationRules;

class UpdateLocationRequest extends StoreLocationRequest
{
    /**
     * Rules to validate this request against.
     *
     * @return ValidationRules
     */
    public function rules(): array
    {
        return LocationRules::rules($this->parameter('location', Location::class));
    }
}
