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

    /** @param list<string>|null $allowedIps */
    public function create(User $user, ?string $description, ?array $allowedIps): NewAccessToken
    {
        $validator = Validator::make(['description' => $description, 'allowed_ips' => $allowedIps], self::rules());
        $validator->after(fn () => self::validateAllowedIps($validator));

        $description = $validator->safe()->string('description')->toString();

        $token = DB::transaction(function () use ($user, $description, $allowedIps): NewAccessToken {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            throw_if($user->apiKeys()->count() >= 25, DisplayException::class, 'You have reached the account limit for number of API keys.');

            return $user->createToken($description, $allowedIps);
        });

        Activity::event('user:api-key.create')
            ->subject($user, $token->accessToken)
            ->property('identifier', $token->accessToken->identifier)
            ->log();

        return $token;
    }
}
