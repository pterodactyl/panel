<?php

namespace Pterodactyl\Http\Requests\Admin\Jexactyl;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class ThemeFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'theme:current' => 'required|string',
        ];
    }
}
