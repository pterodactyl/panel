<?php

namespace Pterodactyl\Http\Controllers\Api\Application\Locations;

use Pterodactyl\Models\Location;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApiAdminRequest;

class StoreLocationRequest extends ApiAdminRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_LOCATIONS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::WRITE;

    /**
     * Rules to validate the request aganist.
     *
     * @return array
     */
    public function rules(): array
    {
        return collect(Location::getCreateRules())->only([
            'long',
            'short',
        ])->toArray();
    }

    /**
     * Rename fields to be more clear in error messages.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'long' => 'Location Description',
            'short' => 'Location Identifier',
        ];
    }
}
