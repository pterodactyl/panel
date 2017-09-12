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
}
