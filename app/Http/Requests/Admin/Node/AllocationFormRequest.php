<?php

namespace Pterodactyl\Http\Requests\Admin\Node;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class AllocationFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'allocation_ip' => 'required|string',
            'allocation_alias' => 'sometimes|nullable|string|max:191',
            'allocation_ports' => 'required|array',
        ];
    }
}
