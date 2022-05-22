<?php

namespace Pterodactyl\Http\Requests\Admin\Jexactyl;

use Illuminate\Validation\Rule;
use Pterodactyl\Traits\Helpers\AvailableLanguages;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class JexactylFormRequest extends AdminFormRequest
{
    use AvailableLanguages;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            'store:enabled' => 'required|bool',
        ];
    }
}
