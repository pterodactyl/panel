<?php

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\User;
use Illuminate\Support\Collection;

interface ApiKeyRepositoryInterface extends RepositoryInterface
{
    /**
     * Get all of the account API keys that exist for a specific user.
     *
     * @param \Pterodactyl\Models\User $user
     * @return \Illuminate\Support\Collection
     */
    public function getAccountKeys(User $user): Collection;

    /**
     * Get all of the application API keys that exist for a specific user.
     *
     * @param \Pterodactyl\Models\User $user
     * @return \Illuminate\Support\Collection
     */
    public function getApplicationKeys(User $user): Collection;

    /**
     * Delete an account API key from the panel for a specific user.
     *
     * @param \Pterodactyl\Models\User $user
     * @param string                   $identifier
     * @return int
     */
    public function deleteAccountKey(User $user, string $identifier): int;

    /**
     * Delete an application API key from the panel for a specific user.
     *
     * @param \Pterodactyl\Models\User $user
     * @param string                   $identifier
     * @return int
     */
    public function deleteApplicationKey(User $user, string $identifier): int;
}
