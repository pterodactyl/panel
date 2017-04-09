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
    private function checkPermission(User $user, Key $key, $permission)
    {
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
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function locationList(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'location-list');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function serverList(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'server-list');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function serverView(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'server-view');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function serverCreate(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'server-create');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function serverDelete(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'server-delete');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function serverEditDetails(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'server-edit-details');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function serverEditContainer(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'server-edit-container');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function serverEditBuild(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'server-edit-build');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function serverEditStartup(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'server-edit-startup');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function serverSuspend(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'server-suspend');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function servrerInstall(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'server-install');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function serverRebuild(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'server-rebuild');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function userServerList(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'user-server-list');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function userServerView(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'user-server-view');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function userServerPower(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'user-server-power');
    }

    /**
     * @param  \Pterodactyl\Models\User    $user
     * @param  \Pterodactyl\Models\APIKey  $key
     * @return bool
     */
    public function userServerCommand(User $user, Key $key)
    {
        return $this->checkPermission($user, $key, 'user-server-command');
    }
}
