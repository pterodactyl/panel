<?php

namespace Pterodactyl\Http\Requests\API\Admin\Locations;

use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\API\Admin\ApiAdminRequest;

class GetLocationsRequest extends ApiAdminRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_LOCATIONS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::READ;
}
