<?php

namespace Pterodactyl\Policies;

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
     * Check if user has permission to start a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function powerStart(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('power-start')->exists();
    }

    /**
     * Check if user has permission to stop a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function powerStop(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('power-stop')->exists();
    }

    /**
     * Check if user has permission to restart a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function powerRestart(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('power-restart')->exists();
    }

    /**
     * Check if user has permission to kill a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function powerKill(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('power-kill')->exists();
    }

    /**
     * Check if user has permission to run a command on a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function sendCommand(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('send-command')->exists();
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
     * Check if user has permission to delete files from a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function deleteFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('delete-files')->exists();
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

    /**
     * Check if user has permission to view subusers for the server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function listSubusers(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('list-subusers')->exists();
    }

    /**
     * Check if user has permission to view specific subuser permissions.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function viewSubuser(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('view-subuser')->exists();
    }

    /**
     * Check if user has permission to edit a subuser.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function editSubuser(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('edit-subuser')->exists();
    }

    /**
     * Check if user has permission to delete a subuser.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function deleteSubuser(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('delete-subuser')->exists();
    }

    /**
     * Check if user has permission to edit a subuser.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function createSubuser(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('create-subuser')->exists();
    }

    /**
     * Check if user has permission to view the server management page.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function viewManage(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('view-manage')->exists();
    }

    /**
     * Check if user has permission to view allocations for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function viewAllocation(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('view-allocation')->exists();
    }

    /**
     * Check if user has permission to set the default connection for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return boolean
     */
    public function setAllocation(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('set-allocation')->exists();
    }

}
