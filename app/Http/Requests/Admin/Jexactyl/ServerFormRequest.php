<?php

namespace Pterodactyl\Http\Requests\Admin\Jexactyl;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class ServerFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'enabled' => 'required|in:true,false',
            'default' => 'required|int|min:1',
            'cost' => 'required|int|min:0',
            'editing' => 'required|in:true,false',
        ];
    }
}
