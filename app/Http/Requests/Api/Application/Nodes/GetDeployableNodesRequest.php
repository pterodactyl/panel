<?php

namespace Pterodactyl\Http\Requests\Api\Application\Nodes;

class GetDeployableNodesRequest extends GetNodesRequest
{
    /**
     * @return string[]
     */
    public function rules(): array
    {
        return [
            'page' => 'integer',
            'memory' => 'required|integer|min:0',
            'disk' => 'required|integer|min:0',
            'location_ids' => 'array',
            'location_ids.*' => 'integer',
        ];
    }
}
