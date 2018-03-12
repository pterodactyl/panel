<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\User;
use Webmozart\Assert\Assert;
use Pterodactyl\Models\DaemonKey;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\DaemonKeyRepositoryInterface;

class DaemonKeyRepository extends EloquentRepository implements DaemonKeyRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
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
     * Return a daemon key with the associated server relation attached.
     *
     * @param string $key
     * @return \Pterodactyl\Models\DaemonKey
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getKeyWithServer(string $key): DaemonKey
    {
        Assert::notEmpty($key, 'Expected non-empty string as first argument passed to ' . __METHOD__);

        try {
            return $this->getBuilder()->with('server')->where('secret', '=', $key)->firstOrFail($this->getColumns());
        } catch (ModelNotFoundException $exception) {
            throw new RecordNotFoundException;
        }
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
        return $this->getBuilder()->with('node')->where('user_id', $user->id)->get($this->getColumns());
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
