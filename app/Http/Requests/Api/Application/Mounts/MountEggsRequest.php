<?php

namespace Pterodactyl\Http\Requests\Api\Application\Mounts;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class MountEggsRequest extends ApplicationApiRequest
{
    protected string $resource = AdminAcl::RESOURCE_MOUNTS;
    protected int $permission = AdminAcl::WRITE;

    public function rules(array $rules = null): array
    {
        return $rules ?? ['eggs' => 'required|exists:eggs,id'];
    }
}
