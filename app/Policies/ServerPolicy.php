<?php
/**
 * Pterodactyl - Panel
 * Copyright (c) 2015 - 2016 Dane Everitt <dane@daneeveritt.com>.
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
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
     */
    public function saveFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('save-files')->exists();
    }

    /**
     * Check if user has permission to move and rename files and folders on a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function moveFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('move-files')->exists();
    }

    /**
     * Check if user has permission to copy folders and files on a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function copyFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('copy-files')->exists();
    }

    /**
     * Check if user has permission to compress files and folders on a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function compressFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('compress-files')->exists();
    }

    /**
     * Check if user has permission to decompress files on a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function decompressFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('decompress-files')->exists();
    }

    /**
     * Check if user has permission to add files to a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
     */
    public function deleteFiles(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('delete-files')->exists();
    }

    /**
     * Check if user has permission to view subusers for the server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
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
     * @return bool
     */
    public function createSubuser(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('create-subuser')->exists();
    }

    /**
     * Check if user has permission to set the default connection for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function setConnection(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('set-connection')->exists();
    }

    /**
     * Check if user has permission to view the startup command used for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function viewStartup(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('view-startup')->exists();
    }

    /**
     * Check if user has permission to edit the startup command used for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function editStartup(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('edit-startup')->exists();
    }

    /**
     * Check if user has permission to view the SFTP information for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function viewSftp(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('view-sftp')->exists();
    }

    /**
     * Check if user has permission to reset the SFTP password for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function resetSftp(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('reset-sftp')->exists();
    }

    /**
     * Check if user has permission to view the SFTP password for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function viewSftpPassword(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('view-sftp-password')->exists();
    }

    /**
     * Check if user has permission to view databases for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function viewDatabases(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('view-databases')->exists();
    }

    /**
     * Check if user has permission to reset database passwords.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function resetDbPassword(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('reset-db-password')->exists();
    }

    /**
     * Check if user has permission to view all tasks for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function listTasks(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('list-tasks')->exists();
    }

    /**
     * Check if user has permission to view a specific task for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function viewTask(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('view-task')->exists();
    }

    /**
     * Check if user has permission to view a toggle a task for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function toggleTask(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('toggle-task')->exists();
    }

    /**
     * Check if user has permission to queue a task for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function queueTask(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('queue-task')->exists();
    }

    /**
     * Check if user has permission to delete a specific task for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function deleteTask(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('delete-task')->exists();
    }

    /**
     * Check if user has permission to create a task for a server.
     *
     * @param  Pterodactyl\Models\User   $user
     * @param  Pterodactyl\Models\Server $server
     * @return bool
     */
    public function createTask(User $user, Server $server)
    {
        if ($this->isOwner($user, $server)) {
            return true;
        }

        return $user->permissions()->server($server)->permission('create-task')->exists();
    }
}
