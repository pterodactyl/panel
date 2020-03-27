<?php

namespace Pterodactyl\Repositories\Eloquent;

use Pterodactyl\Models\Subuser;
use Pterodactyl\Exceptions\Repository\RecordNotFoundException;
use Pterodactyl\Contracts\Repository\SubuserRepositoryInterface;

class SubuserRepository extends EloquentRepository implements SubuserRepositoryInterface
{
    /**
     * Return the model backing this repository.
     *
     * @return string
     */
    public function model()
    {
        return Subuser::class;
    }

    /**
     * Returns a subuser model for the given user and server combination. If no record
     * exists an exception will be thrown.
     *
     * @param int $server
     * @param string $uuid
     * @return \Pterodactyl\Models\Subuser
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getUserForServer(int $server, string $uuid): Subuser
    {
        /** @var \Pterodactyl\Models\Subuser $model */
        $model = $this->getBuilder()
            ->with('server', 'user')
            ->select('subusers.*')
            ->join('users', 'users.id', '=', 'subusers.user_id')
            ->where('subusers.server_id', $server)
            ->where('users.uuid', $uuid)
            ->firstOrFail();

        return $model;
    }

    /**
     * Return a subuser with the associated server relationship.
     *
     * @param \Pterodactyl\Models\Subuser $subuser
     * @param bool $refresh
     * @return \Pterodactyl\Models\Subuser
     */
    public function loadServerAndUserRelations(Subuser $subuser, bool $refresh = false): Subuser
    {
        if (! $subuser->relationLoaded('server') || $refresh) {
            $subuser->load('server');
        }

        if (! $subuser->relationLoaded('user') || $refresh) {
            $subuser->load('user');
        }

        return $subuser;
    }

    /**
     * Return a subuser with the associated permissions relationship.
     *
     * @param \Pterodactyl\Models\Subuser $subuser
     * @param bool $refresh
     * @return \Pterodactyl\Models\Subuser
     */
    public function getWithPermissions(Subuser $subuser, bool $refresh = false): Subuser
    {
        if (! $subuser->relationLoaded('permissions') || $refresh) {
            $subuser->load('permissions');
        }

        if (! $subuser->relationLoaded('user') || $refresh) {
            $subuser->load('user');
        }

        return $subuser;
    }

    /**
     * Return a subuser and associated permissions given a user_id and server_id.
     *
     * @param int $user
     * @param int $server
     * @return \Pterodactyl\Models\Subuser
     *
     * @throws \Pterodactyl\Exceptions\Repository\RecordNotFoundException
     */
    public function getWithPermissionsUsingUserAndServer(int $user, int $server): Subuser
    {
        $instance = $this->getBuilder()->with('permissions')->where([
            ['user_id', '=', $user],
            ['server_id', '=', $server],
        ])->first();

        if (is_null($instance)) {
            throw new RecordNotFoundException;
        }

        return $instance;
    }
}
