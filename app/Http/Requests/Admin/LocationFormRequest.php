<?php

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\Location;

class LocationFormRequest extends AdminFormRequest
{
    /**
     * Setup the validation rules to use for these requests.
     *
     * @return array
     */
    public function rules()
    {
        if ($this->method() === 'PATCH') {
            return Location::getRulesForUpdate($this->route()->parameter('location')->id);
        }

        return Location::getRules();
    }
}
