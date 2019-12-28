<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\User;
use Pterodactyl\Models\Subuser;

class SubuserTransformer extends BaseClientTransformer
{
    /**
     * @var array
     */
    protected $defaultIncludes = ['user'];

    /**
     * Return the resource name for the JSONAPI output.
     *
     * @return string
     */
    public function getResourceName(): string
    {
        return Subuser::RESOURCE_NAME;
    }

    /**
     * Transforms a subuser into a model that can be shown to a front-end user.
     *
     * @param \Pterodactyl\Models\Subuser $model
     * @return array|void
     */
    public function transform(Subuser $model)
    {
        return [
            'permissions' => $model->permissions->pluck('permission'),
        ];
    }

    /**
     * Include the permissions associated with this subuser.
     *
     * @param \Pterodactyl\Models\Subuser $model
     * @return \League\Fractal\Resource\Item
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function includeUser(Subuser $model)
    {
        return $this->item($model->user, $this->makeTransformer(UserTransformer::class), User::RESOURCE_NAME);
    }
}
