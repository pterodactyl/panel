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

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\User;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\DaemonKey;
use Illuminate\Support\Collection;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;

class DaemonKeyRepository extends EloquentRepository implements DaemonKeyRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function model()
    {
        return DaemonKey::class;
    }

    /**
     * Load the server and user relations onto a key model.
     *
     * @param \Pterodactyl\Models\DaemonKey $key
     * @param bool                          $refresh
     * @return \Pterodactyl\Models\DaemonKey
     */
    public function loadServerAndUserRelations(DaemonKey $key, bool $refresh = false): DaemonKey
    {
        if (! $key->relationLoaded('server') || $refresh) {
            $key->load('server');
        }

        if (! $key->relationLoaded('user') || $refresh) {
            $key->load('user');
        }

        return $key;
    }

    /**
     * {@inheritdoc}
     */
    public function getServerKeys($server)
    {
        Assert::integerish($server, 'First argument passed to getServerKeys must be integer, received %s.');

        return $this->getBuilder()->where('server_id', $server)->get($this->getColumns());
    }

    /**
     * {@inheritdoc}
     */
    public function getKeyWithServer($key)
    {
        Assert::stringNotEmpty($key, 'First argument passed to getServerByKey must be string, received %s.');

        $instance = $this->getBuilder()->with('server')->where('secret', '=', $key)->first();
        if (is_null($instance)) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }

    /**
     * Get all of the keys for a specific user including the information needed
     * from their server relation for revocation on the daemon.
     *
     * @param \Pterodactyl\Models\User $user
     * @return \Illuminate\Support\Collection
     */
    public function getKeysForRevocation(User $user): Collection
    {
        return $this->getBuilder()->with('server:id,uuid,node_id')->where('user_id', $user->id)->get($this->getColumns());
    }

    /**
     * Delete an array of daemon keys from the database. Used primarily in
     * conjunction with getKeysForRevocation.
     *
     * @param array $ids
     * @return bool|int
     */
    public function deleteKeys(array $ids)
    {
        return $this->getBuilder()->whereIn('id', $ids)->delete();
    }
}
