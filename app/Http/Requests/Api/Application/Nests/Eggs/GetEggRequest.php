<?php

namespace App\Http\Requests\Api\Application\Nests\Eggs;

use App\Models\Egg;
use App\Models\Nest;
use App\Services\Acl\Api\AdminAcl;
use App\Http\Requests\Api\Application\ApplicationApiRequest;

class GetEggRequest extends ApplicationApiRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_EGGS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;

    /**
     * Determine if the requested egg exists for the selected nest.
     *
     * @return bool
     */
    public function resourceExists(): bool
    {
        return $this->getModel(Nest::class)->id === $this->getModel(Egg::class)->nest_id;
    }
}
