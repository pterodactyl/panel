<?php

namespace Pterodactyl\Http\Requests\Admin\Jexactyl;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class ThemeFormRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'theme:admin' => 'required|string|in:jexactyl,dark,light,blue,minecraft',
        ];
    }
}
