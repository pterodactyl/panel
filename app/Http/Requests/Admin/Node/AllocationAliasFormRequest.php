<?php

namespace Pterodactyl\Http\Requests\Admin\Node;

use Pterodactyl\Http\Requests\Admin\AdminFormRequest;

class AllocationAliasFormRequest extends AdminFormRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'alias' => 'present|nullable|string',
            'allocation_id' => 'required|numeric|exists:allocations,id',
        ];
    }
}
