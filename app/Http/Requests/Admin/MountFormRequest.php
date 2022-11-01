<?php

namespace Pterodactyl\Http\Requests\Admin;

use Pterodactyl\Models\Mount;

class MountFormRequest extends AdminFormRequest
{
    /**
     * Set up the validation rules to use for these requests.
     */
    public function rules(): array
    {
        if ($this->method() === 'PATCH') {
            /** @var Mount $mount */
            $mount = $this->route()->parameter('mount');

            return Mount::getRulesForUpdate($mount->id);
        }

        return Mount::getRules();
    }
}
