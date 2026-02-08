<?php

namespace Pterodactyl\Http\Requests\Admin\Node;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class NodeCloneRequest extends AdminFormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|regex:/^([\w .-]{1,100})$/',
            'location_id' => 'required|exists:locations,id',
            'fqdn' => 'required|string',
            'description' => 'string|nullable',
        ];
    }
}
