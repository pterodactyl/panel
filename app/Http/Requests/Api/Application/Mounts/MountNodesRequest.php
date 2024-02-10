<?php

namespace Pterodactyl\Http\Requests\Api\Application\Mounts;

use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class MountNodesRequest extends ApplicationApiRequest
{
    public function rules(array $rules = null): array
    {
        return $rules ?? ['nodes' => 'required|exists:nodes,id'];
    }
}
