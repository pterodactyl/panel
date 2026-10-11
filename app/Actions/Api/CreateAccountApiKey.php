<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Api;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Contracts\Api\CreatesAccountApiKeys;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Laravel\Sanctum\NewAccessToken;
use Pterodactyl\Facades\Activity;
use Pterodactyl\Models\User;
use Pterodactyl\Validation\Concerns\ValidatesAllowedIps;

final readonly class CreateAccountApiKey implements CreatesAccountApiKeys
{
    use ValidatesAllowedIps;

    /**
     * An account API key's fields: its description, held to the rules of an API key's memo,
     * and the IP addresses or CIDR ranges it may be used from.
     *
     * @return NormalizedValidationRules
     */
    public static function rules(): array
    {
        $rules = CreateApiKey::rules();

        return [
            'description' => $rules['memo'],
            'allowed_ips' => $rules['allowed_ips'],
            'allowed_ips.*' => $rules['allowed_ips.*'],
        ];
    }

    /**
     * Validates account API key input and returns the description and the allowed IPs, or
     * null when none were given.
     *
     * @param  array<string, mixed>  $input
     * @return array{description: string, allowed_ips: list<string>|null}
     */
    public static function attributes(array $input): array
    {
        $validator = Validator::make($input, self::rules());
        $validator->after(fn () => self::validateAllowedIps($validator));

        $input = $validator->safe();

        return [
            'description' => $input->string('description')->toString(),
            'allowed_ips' => $input->filled('allowed_ips')
                ? array_map(fn (int|string $key): string => $input->string('allowed_ips.'.$key)->toString(), array_keys($input->array('allowed_ips')))
                : null,
        ];
    }

    /** @param list<string>|null $allowedIps */
    public function create(User $user, ?string $description, ?array $allowedIps): NewAccessToken
    {
        $attributes = self::attributes(['description' => $description, 'allowed_ips' => $allowedIps]);

        $token = DB::transaction(function () use ($user, $attributes): NewAccessToken {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            throw_if($user->apiKeys()->count() >= 25, DisplayException::class, 'You have reached the account limit for number of API keys.');

            return $user->createToken($attributes['description'], $attributes['allowed_ips']);
        });

        Activity::event('user:api-key.create')
            ->subject($user, $token->accessToken)
            ->property('identifier', $token->accessToken->identifier)
            ->log();

        return $token;
    }
}
