<?php

namespace Pterodactyl\Http\Requests\Admin\Nest;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class StoreNestFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:1|max:191',
            'description' => 'string|nullable',
        ];
    }
}
