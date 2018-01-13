<?php

namespace Pterodactyl\Http\Requests\API\Admin\Users;

use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\API\Admin\ApiAdminRequest;

class StoreUserRequest extends ApiAdminRequest
{
    /**
     * @var string
     */
    protected $resource = AdminAcl::RESOURCE_USERS;

    /**
     * @var int
     */
    protected $permission = AdminAcl::WRITE;

    /**
     * Return the validation rules for this request.
     *
     * @return array
     */
    public function rules(): array
    {
        return collect(User::getCreateRules())->only([
            'external_id',
            'email',
            'username',
            'name_first',
            'name_last',
            'password',
            'language',
            'root_admin',
        ])->toArray();
    }

    /**
     * Rename some fields to be more user friendly.
     *
     * @return array
     */
    public function attributes()
    {
        return [
            'external_id' => 'Third Party Identifier',
            'name_first' => 'First Name',
            'name_last' => 'Last Name',
            'root_admin' => 'Root Administrator Status',
        ];
    }
}
