<?php

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\Location;

class LocationFormRequest extends AdminFormRequest
{
    /**
     * Set up the validation rules to use for these requests.
     */
    public function rules(): array
    {
        if ($this->method() === 'PATCH') {
            /** @var Location $location */
            $location = $this->route()->parameter('location');
            return Location::getRulesForUpdate($location->id);
        }

        return Location::getRules();
    }
}
