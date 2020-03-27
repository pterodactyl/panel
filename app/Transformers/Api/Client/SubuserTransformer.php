<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\Subuser;

class SubuserTransformer extends BaseClientTransformer
{
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
     * @throws \Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException
     */
    public function transform(Subuser $model)
    {
        return array_merge(
            $this->makeTransformer(UserTransformer::class)->transform($model->user),
            ['permissions' => $model->permissions]
        );
    }
}
