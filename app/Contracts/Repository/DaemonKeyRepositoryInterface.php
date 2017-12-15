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

namespace Pterodactyl\Contracts\Repository;

use Pterodactyl\Models\User;
use Pterodactyl\Models\DaemonKey;
use Illuminate\Support\Collection;

interface DaemonKeyRepositoryInterface extends RepositoryInterface
{
    /**
     * String prepended to keys to identify that they are managed internally and not part of the user API.
     */
    const INTERNAL_KEY_IDENTIFIER = 'i_';

    /**
     * Load the server and user relations onto a key model.
     *
     * @param \Pterodactyl\Models\DaemonKey $key
     * @param bool                          $refresh
     * @return \Pterodactyl\Models\DaemonKey
     */
    public function loadServerAndUserRelations(DaemonKey $key, bool $refresh = false): DaemonKey;

    /**
     * Gets the daemon keys associated with a specific server.
     *
     * @param int $server
     * @return \Illuminate\Support\Collection
     */
    public function getServerKeys($server);

    /**
     * Return a daemon key with the associated server relation attached.
     *
     * @param string $key
     * @return \Pterodactyl\Models\DaemonKey
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getKeyWithServer($key);

    /**
     * Get all of the keys for a specific user including the information needed
     * from their server relation for revocation on the daemon.
     *
     * @param \Pterodactyl\Models\User $user
     * @return \Illuminate\Support\Collection
     */
    public function getKeysForRevocation(User $user): Collection;

    /**
     * Delete an array of daemon keys from the database. Used primarily in
     * conjunction with getKeysForRevocation.
     *
     * @param array $ids
     * @return bool|int
     */
    public function deleteKeys(array $ids);
}
