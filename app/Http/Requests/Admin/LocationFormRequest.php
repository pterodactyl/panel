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
            return Location::getRulesForUpdate($this->route()->parameter('location')->id); // @phpstan-ignore property.nonObject
        }

        return Location::getRules();
    }
}
