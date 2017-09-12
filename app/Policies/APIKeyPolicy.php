<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2017 Dane Everitt <dane@daneeveritt.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Pterodactyl\Policies;

use Cache;
use Carbon;
use Pterodactyl\Models\User;
use Pterodactyl\Models\APIKey as Key;
use Pterodactyl\Models\APIPermission as Permission;

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

        return $permissions->search($permission, true) !== false;
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
