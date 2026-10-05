<?php

declare(strict_types=1);

namespace Pterodactyl\Services\Users;

use Pterodactyl\Models\User;

class UserFinderService
{
    public function byExternalId(string $externalId): User
    {
        return User::query()->where('external_id', $externalId)->firstOrFail();
    }
}
