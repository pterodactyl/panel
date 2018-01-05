<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * This software is licensed under the terms of the MIT license.
 * https://opensource.org/licenses/MIT
 */

namespace Pterodactyl\Policies;

use Cache;
use Carbon;
use Pterodactyl\Models\User;
use Pterodactyl\Models\APIKey as Key;

class APIKeyPolicy
{
    /**
     * Checks if the API key has permission to perform an action.
     *
     * @param \Pterodactyl\Models\User   $user
     * @param \Pterodactyl\Models\APIKey $key
     * @param string                     $permission
     * @return bool
     */
    protected function checkPermission(User $user, Key $key, $permission)
    {
        // Non-administrative users cannot use administrative routes.
        if (! starts_with($key, 'user.') && ! $user->root_admin) {
            return false;
        }

        // We don't tag this cache key with the user uuid because the key is already unique,
        // and multiple users are not defiend for a single key.
        $permissions = Cache::remember('APIKeyPolicy.' . $key->public, Carbon::now()->addSeconds(5), function () use ($key) {
            return $key->permissions()->get()->transform(function ($item) {
                return $item->permission;
            })->values();
        });

        return $permissions->setSearchTerm($permission, true) !== false;
    }

    /**
     * Determine if a user has permission to perform this action against the system.
     *
     * @param \Pterodactyl\Models\User   $user
     * @param string                     $permission
     * @param \Pterodactyl\Models\APIKey $key
     * @return bool
     */
    public function before(User $user, $permission, Key $key)
    {
        return $this->checkPermission($user, $key, $permission);
    }
}
