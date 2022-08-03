<?php

namespace Pterodactyl\Http\Requests\Admin\Jexactyl;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class RegistrationFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'registration:enabled' => 'required|in:true,false',

            'discord:enabled' => 'required|in:true,false',
            'discord:id' => 'required|int',
            'discord:secret' => 'required|string',

            'registration:cpu' => 'required|int',
            'registration:memory' => 'required|int',
            'registration:disk' => 'required|int',
            'registration:slot' => 'required|int',
            'registration:port' => 'required|int',
            'registration:backup' => 'required|int',
            'registration:database' => 'required|int',
        ];
    }
}
