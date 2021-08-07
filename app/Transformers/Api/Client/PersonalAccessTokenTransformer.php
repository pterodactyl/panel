<?php

namespace Pterodactyl\Transformers\Api\Client;

use Pterodactyl\Models\PersonalAccessToken;
use Pterodactyl\Transformers\Api\Transformer;

class PersonalAccessTokenTransformer extends Transformer
{
    public function getResourceName(): string
    {
        return PersonalAccessToken::RESOURCE_NAME;
    }

    public function transform(PersonalAccessToken $model): array
    {
        return [
            'token_id' => $model->token_id,
            'description' => $model->description,
            'abilities' => $model->abilities ?? [],
            'last_used_at' => self::formatTimestamp($model->last_used_at),
            'created_at' => self::formatTimestamp($model->created_at),
            'updated_at' => self::formatTimestamp($model->updated_at),
        ];
    }
}
