<?php

namespace Pterodactyl\Policies;

use Log;
use Debugbar;
use Pterodactyl\Models\User;
use Pterodactyl\Models\Server;

class ServerPolicy
{

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if current user is the owner of a server.
     *
     * @param  Pterodactyl\Models\User    $user
     * @param  Pterodactyl\Models\Server  $server
     * @return boolean
     */
    protected function isOwner(User $user, Server $server)
    {
        return $server->owner === $user->id;
    }

    /**
     * Runs before any of the functions are called. Used to determine if user is root admin, if so, ignore permissions.
     *
     * @param  Pterodactyl\Models\User $user
     * @param  string $ability
     * @return boolean
     */
    public function before(User $user, $ability)
    {
        if ($user->root_admin === 1) {
            return true;
        }
    }

    /**
     * Check if user has permission to control power for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function power(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('power')->exists();
    }

    /**
     * Check if user has permission to run a command on a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function command(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('command')->exists();
    }

    /**
     * Check if user has permission to list files on a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function listFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('list-files')->exists();
    }

    /**
     * Check if user has permission to edit files on a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function editFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('edit-files')->exists();
    }

    /**
     * Check if user has permission to save files on a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function saveFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('save-files')->exists();
    }

    /**
     * Check if user has permission to add files to a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function addFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('add-files')->exists();
    }

    /**
     * Check if user has permission to upload files to a server.
     * This permission relies on the user having the 'add-files' permission as well due to page authorization.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function uploadFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('upload-files')->exists();
    }

    /**
     * Check if user has permission to download files from a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function downloadFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('download-files')->exists();
    }

    /**
     * Check if user has permission to change the default connection information.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function setConnection(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('set-connection')->exists();
    }

}
