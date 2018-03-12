<?php

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
     * Return a daemon key with the associated server relation attached.
     *
     * @param string $key
     * @return \Pterodactyl\Models\DaemonKey
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getKeyWithServer(string $key): DaemonKey;

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
