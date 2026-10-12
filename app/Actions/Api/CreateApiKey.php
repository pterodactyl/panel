<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Api;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Pterodactyl\Contracts\Api\CreatesApiKeys;
use Pterodactyl\Models\ApiKey;
use Pterodactyl\Services\Acl\Api\AdminAcl;
use Pterodactyl\Validation\Concerns\ValidatesAllowedIps;

final readonly class CreateApiKey implements CreatesApiKeys
{
    use ValidatesAllowedIps;

    /**
     * An API key's fields: its memo, the user it belongs to, and the IP addresses or CIDR
     * ranges it may be used from. Each allowed IP must also parse as an address or a range
     * (see ValidatesAllowedIps).
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        return [
            'memo' => ['required', 'nullable', 'string', 'max:500'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'allowed_ips' => ['nullable', 'array', 'list', 'max:50'],
            'allowed_ips.*' => ['string'],
        ];
    }

    /**
     * The resource permissions of an application key: an r_* column for each AdminAcl
     * resource, holding AdminAcl::NONE or AdminAcl::READ and AdminAcl::WRITE combined.
     *
     * @return NormalizedValidationRules
     */
    public static function permissionRules(): array
    {
        $rules = [];
        foreach (AdminAcl::getResourceList() as $resource) {
            $rules[AdminAcl::COLUMN_IDENTIFIER.$resource] = ['integer', 'min:'.AdminAcl::NONE, 'max:'.(AdminAcl::READ | AdminAcl::WRITE)];
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $permissions
     */
    public function create(int $keyType, array $input, array $permissions = []): ApiKey
    {
        $validator = Validator::make($input, self::rules());
        $validator->after(fn () => self::validateAllowedIps($validator));

        $attributes = [
            ...$validator->safe(['memo', 'user_id', 'allowed_ips']),
            'key_type' => $keyType,
            'identifier' => ApiKey::generateTokenIdentifier($keyType),
            'token' => Crypt::encrypt(Str::random(ApiKey::KEY_LENGTH)),
        ];

        // A resource left out keeps the column default of AdminAcl::NONE.
        $columns = Validator::validate($permissions, self::permissionRules());
        if ($keyType === ApiKey::TYPE_APPLICATION) {
            $attributes = [...$attributes, ...$columns];
        }

        return ApiKey::query()->forceCreate($attributes)->refresh();
    }
}
