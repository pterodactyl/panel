<?php

namespace Pterodactyl\Http\Requests\Api\Application\Databases;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class DatabaseNodesRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_DATABASE_HOSTS;

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
        return $rules ?? ['nodes' => 'required|exists:nodes,id'];
    }
}
