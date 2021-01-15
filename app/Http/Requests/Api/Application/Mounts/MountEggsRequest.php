<?php

namespace Pterodactyl\Http\Requests\Api\Application\Mounts;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class MountEggsRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_MOUNTS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::WRITE;

    /**
     * ?
     *
     * @param array|null $rules
     *
     * @return array
     */
    public function rules(array $rules = null): array
    {
        return $rules ?? ['eggs' => 'required|exists:eggs,id'];
    }
}
