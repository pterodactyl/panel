<?php

declare(strict_types=1);

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Exceptions\Transformer\InvalidTransformerLevelException;
use Pterodactyl\Extensions\Scribe\Attributes\ResponseField;
use Pterodactyl\Models\Subuser;

#[ResponseField('permissions', schema: ['type' => 'array', 'items' => ['type' => 'string'], 'example' => ['control.console', 'file.read']])]
class SubuserTransformer extends BaseClientTransformer
{
    protected array $eagerLoads = ['user'];

    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Subuser::RESOURCE_NAME;
    }

    /**
     * Transforms a subuser into a model that can be shown to a front-end user.
     *
     * @return ApiPayload
     *
     * @throws InvalidTransformerLevelException
     */
    public function transform(Subuser $model): array
    {
        return array_merge(
            $this->makeTransformer(UserTransformer::class)->transform($model->loadMissing('user')->user),
            ['permissions' => $model->permissions]
        );
    }
}
