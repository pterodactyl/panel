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
use Pterodactyl\Models\Server;

class ServerPolicy
{
    /**
     * Checks if the user has the given permission on/for the server.
     *
     * @param \Pterodactyl\Models\User   $user
     * @param \Pterodactyl\Models\Server $server
     * @param string                     $permission
     * @return bool
     */
    protected function checkPermission(User $user, Server $server, $permission)
    {
        $permissions = Cache::remember('ServerPolicy.' . $user->uuid . $server->uuid, Carbon::now()->addSeconds(5), function () use ($user, $server) {
            return $user->permissions()->server($server)->get()->transform(function ($item) {
                return $item->permission;
            })->values();
        });

        return $permissions->search($permission, true) !== false;
    }

    /**
     * Runs before any of the functions are called. Used to determine if user is root admin, if so, ignore permissions.
     *
     * @param \Pterodactyl\Models\User   $user
     * @param string                     $ability
     * @param \Pterodactyl\Models\Server $server
     * @return bool
     */
    public function before(User $user, $ability, Server $server)
    {
        if ($user->root_admin || $server->owner_id === $user->id) {
            return true;
        }

        return $this->checkPermission($user, $server, $ability);
    }

    /**
     * This is a horrendous hack to avoid Laravel's "smart" behavior that does
     * not call the before() function if there isn't a function matching the
     * policy permission.
     *
     * @param string $name
     * @param mixed  $arguments
     */
    public function __call($name, $arguments)
    {
        // do nothing
    }
}
