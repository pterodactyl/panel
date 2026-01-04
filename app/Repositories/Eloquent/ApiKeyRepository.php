<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\User;
use Pterodactyl\Models\ApiKey;
use Illuminate\Support\Collection;
use Pterodactyl\Contracts\Repository\ApiKeyRepositoryInterface;

class ApiKeyRepository extends EloquentRepository implements ApiKeyRepositoryInterface
{
    /**
     * Return the model backing this repository.
     */
    public function model(): string
    {
        return ApiKey::class;
    }

    /**
     * Get all the account API keys that exist for a specific user.
     */
    public function getAccountKeys(User $user): Collection
    {
        return $this->getBuilder()->where('user_id', $user->id)
            ->where('key_type', ApiKey::TYPE_ACCOUNT)
            ->get($this->getColumns());
    }

    /**
     * Get all the application API keys. If a user is provided, filter by that user.
     */
    public function getApplicationKeys(?User $user = null): Collection
    {
        $instance = $this->getBuilder()->where('key_type', ApiKey::TYPE_APPLICATION);

        if ($user) {
            $instance->where('user_id', $user->id);
        }

        return $instance->get($this->getColumns());
    }

    /**
     * Delete an application API key. If a user is provided, ensure it belongs to them.
     */
    public function deleteApplicationKey(?User $user = null, string $identifier): int
    {
        $query = $this->getBuilder()
            ->where('key_type', ApiKey::TYPE_APPLICATION)
            ->where('identifier', $identifier);

        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query->delete();
    }

    /**
     * Delete an account API key from the panel for a specific user.
     */
    public function deleteAccountKey(User $user, string $identifier): int
    {
        return $this->getBuilder()->where('user_id', $user->id)
            ->where('key_type', ApiKey::TYPE_ACCOUNT)
            ->where('identifier', $identifier)
            ->delete();
    }

    /**
     * Delete an application API key from the panel.
     */
    public function deleteApplicationKey(string $identifier): int
    {
        return $this->getBuilder()
            ->where('key_type', ApiKey::TYPE_APPLICATION)
            ->where('identifier', $identifier)
            ->delete();
    }
}
