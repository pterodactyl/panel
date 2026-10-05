<?php

declare(strict_types=1);

namespace Pterodactyl\Actions\Api;

use Illuminate\Support\Facades\DB;
use Pterodactyl\Contracts\Api\CreatesAccountApiKeys;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Extensions\Laravel\Sanctum\NewAccessToken;
use Pterodactyl\Models\User;

final readonly class CreateAccountApiKey implements CreatesAccountApiKeys
{
    /** @param list<string>|null $allowedIps */
    public function create(User $user, ?string $description, ?array $allowedIps): NewAccessToken
    {
        return DB::transaction(function () use ($user, $description, $allowedIps): NewAccessToken {
            $user = User::query()->lockForUpdate()->findOrFail($user->id);
            throw_if($user->apiKeys()->count() >= 25, DisplayException::class, 'You have reached the account limit for number of API keys.');

            return $user->createToken($description, $allowedIps);
        });
    }
}
