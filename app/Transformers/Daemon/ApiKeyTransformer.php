<?php
/*
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

namespace Pterodactyl\Transformers\Daemon;

use Carbon\Carbon;
use Pterodactyl\Models\DaemonKey;
use Pterodactyl\Models\Permission;
use League\Fractal\TransformerAbstract;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;

class ApiKeyTransformer extends TransformerAbstract
{
    /**
     * @var \Carbon\Carbon
     */
    protected $carbon;

    /**
     * @var \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface
     */
    protected $repository;

    /**
     * ApiKeyTransformer constructor.
     *
     * @param \Carbon\Carbon                                               $carbon
     * @param \Pterodactyl\Contracts\Repository\SubuserRepositoryInterface $repository
     */
    public function __construct(Carbon $carbon, SubuserRepositoryInterface $repository)
    {
        $this->carbon = $carbon;
        $this->repository = $repository;
    }

    /**
     * Return a listing of servers that a daemon key can access.
     *
     * @param \Pterodactyl\Models\DaemonKey $key
     * @return array
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function transform(DaemonKey $key)
    {
        if ($key->user_id === $key->server->owner_id) {
            return [
                'id' => $key->server->uuid,
                'is_temporary' => true,
                'expires_in' => max($this->carbon->now()->diffInSeconds($key->expires_at, false), 0),
                'permissions' => ['s:*'],
            ];
        }

        $subuser = $this->repository->getWithPermissionsUsingUserAndServer($key->user_id, $key->server_id);

        $permissions = $subuser->permissions->pluck('permission')->toArray();
        $mappings = Permission::getPermissions(true);
        $daemonPermissions = [];

        foreach ($permissions as $permission) {
            if (! is_null($mappings[$permission])) {
                $daemonPermissions[] = $mappings[$permission];
            }
        }

        return [
            'id' => $key->server->uuid,
            'is_temporary' => true,
            'expires_in' => max($this->carbon->now()->diffInSeconds($key->expires_at, false), 0),
            'permissions' => $daemonPermissions,
        ];
    }
}
