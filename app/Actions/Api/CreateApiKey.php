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
     * Validates API key input and returns the model attributes it describes. A key given
     * no allowed IPs, or null, can be used from any address.
     *
     * @param  ActionInput  $input
     * @return array{memo: string, user_id: int, allowed_ips?: list<string>}
     */
    public static function attributes(array $input): array
    {
        $validator = Validator::make($input, self::rules());
        $validator->after(fn () => self::validateAllowedIps($validator));

        $input = $validator->safe();

        $attributes = [
            'memo' => $input->string('memo')->toString(),
            'user_id' => $input->integer('user_id'),
        ];

        if ($input->filled('allowed_ips')) {
            $attributes['allowed_ips'] = array_map(fn (int|string $key): string => $input->string('allowed_ips.'.$key)->toString(), array_keys($input->array('allowed_ips')));
        }

        return $attributes;
    }

    /**
     * Validates resource permissions and returns them as integers, keyed by column. Only
     * the permissions given are returned, so the rest keep their stored values, or the
     * column default of AdminAcl::NONE on a new key.
     *
     * @param  ActionInput  $permissions
     * @return array<string, int>
     */
    public static function permissions(array $permissions): array
    {
        $input = Validator::make($permissions, self::permissionRules())->safe();

        $columns = [];
        foreach (array_keys(self::permissionRules()) as $column) {
            if ($input->has($column)) {
                $columns[$column] = $input->integer($column);
            }
        }

        return $columns;
    }

    /**
     * @param  ActionInput  $input
     * @param  ActionInput  $permissions
     */
    public function create(int $keyType, array $input, array $permissions = []): ApiKey
    {
        $attributes = [
            ...self::attributes($input),
            'key_type' => $keyType,
            'identifier' => ApiKey::generateTokenIdentifier($keyType),
            'token' => Crypt::encrypt(Str::random(ApiKey::KEY_LENGTH)),
        ];

        $columns = self::permissions($permissions);
        if ($keyType === ApiKey::TYPE_APPLICATION) {
            $attributes = [...$attributes, ...$columns];
        }

        return ApiKey::query()->forceCreate($attributes)->refresh();
    }
}
