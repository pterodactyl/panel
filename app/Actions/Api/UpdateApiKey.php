<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Api;

use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Api\UpdatesApiKeys;
use Pterodactyl\Models\ApiKey;

final readonly class UpdateApiKey implements UpdatesApiKeys
{
    /**
     * Update an API key's attributes without regenerating its token.
     *
     * @param  ActionInput  $input
     * @param  ActionInput  $permissions
     */
    public function update(ApiKey $key, array $input, array $permissions = []): ApiKey
    {
        $attributes = $this->attributes($input);

        $columns = CreateApiKey::permissions($permissions);
        if ($key->key_type === ApiKey::TYPE_APPLICATION) {
            $attributes = [...$attributes, ...$columns];
        }

        $key->forceFill($attributes)->saveOrFail();

        return $key;
    }

    /**
     * Validates update input and returns the attributes it changes: the memo, when it is
     * given. A key's owner and allowed IPs are set when it is created.
     *
     * @param  ActionInput  $input
     * @return array{memo?: string}
     */
    private function attributes(array $input): array
    {
        $input = Validator::make($input, ['memo' => ['sometimes', ...CreateApiKey::rules()['memo']]])->safe();

        return $input->has('memo') ? ['memo' => $input->string('memo')->toString()] : [];
    }
}
