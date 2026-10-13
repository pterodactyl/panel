<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Api;

use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Api\UpdatesApiKeys;
use Pterodactyl\Models\ApiKey;

final readonly class UpdateApiKey implements UpdatesApiKeys
{
    public function update(ApiKey $key, array $input, array $permissions = []): ApiKey
    {
        $attributes = Validator::validate($input, ['memo' => ['sometimes', ...CreateApiKey::rules()['memo']]]);

        $columns = Validator::validate($permissions, CreateApiKey::permissionRules());
        if ($key->key_type === ApiKey::TYPE_APPLICATION) {
            $attributes = [...$attributes, ...$columns];
        }

        $key->forceFill($attributes)->saveOrFail();

        return $key;
    }
}
