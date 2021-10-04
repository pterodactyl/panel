<?php

namespace Pterodactyl\Http\Requests\Api\Application\Users;

use Pterodactyl\Models\User;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Http\Requests\Api\Application\ApplicationApiRequest;

class StoreUserRequest extends ApplicationApiRequest
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
     */
    public function rules(array $rules = null): array
    {
        $rules = $rules ?? User::getRules();

        $response = collect($rules)->only([
            'external_id',
            'email',
            'username',
            'password',
            'language',
            'root_admin',
        ])->toArray();

        $response['first_name'] = $rules['name_first'];
        $response['last_name'] = $rules['name_last'];

        return $response;
    }

    /**
     * @return array
     */
    public function validated()
    {
        $data = parent::validated();

        $data['name_first'] = $data['first_name'];
        $data['name_last'] = $data['last_name'];

        unset($data['first_name'], $data['last_name']);

        return $data;
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
