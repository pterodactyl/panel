<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\PersonalAccessToken;

class PersonalAccessTokenTransformer extends BaseClientTransformer
{
    /**
     * @return string
     */
    public function getResourceName(): string
    {
        return PersonalAccessToken::RESOURCE_NAME;
    }

    /**
     * @param \Pterodactyl\Models\PersonalAccessToken $model
     * @return array
     */
    public function transform(PersonalAccessToken $model): array
    {
        return [
            'token_id' => $model->token_id,
            'description' => $model->description,
            'abilities' => $model->abilities ?? [],
            'last_used_at' => $model->last_used_at ? $model->last_used_at->toIso8601String() : null,
            'created_at' => $model->created_at->toIso8601String(),
            'updated_at' => $model->updated_at->toIso8601String(),
        ];
    }
}
