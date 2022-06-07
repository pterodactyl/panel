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
            'store:enabled' => 'required|in:true,false',
            'store:cost:cpu' => 'required|int|min:1',
            'store:cost:memory' => 'required|int|min:1',
            'store:cost:disk' => 'required|int|min:1',
            'store:cost:slot' => 'required|int|min:1',
            'store:cost:port' => 'required|int|min:1',
            'store:cost:backup' => 'required|int|min:1',
            'store:cost:database' => 'required|int|min:1',
        ];
    }
}
