<?php

namespace Pterodactyl\Models;

use Illuminate\Support\Collection;

class Permission extends Validable
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    const RESOURCE_NAME = 'subuser_permission';

    /**
     * Should timestamps be used on this model.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permissions';

    /**
     * Fields that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * Cast values to correct type.
     *
     * @var array
     */
    protected $casts = [
        'subuser_id' => 'integer',
    ];

    /**
     * @var array
     */
    public static $validationRules = [
        'subuser_id' => 'required|numeric|min:1',
        'permission' => 'required|string',
    ];

    /**
     * All of the permissions available on the system. You should use self::permissions()
     * to retrieve them, and not directly access this array as it is subject to change.
     *
     * @var array
     * @see \Pterodactyl\Models\Permission::permissions()
     */
    protected static $permissions = [
        'websocket' => [
            // Allows the user to connect to the server websocket, this will give them
            // access to view the console output as well as realtime server stats (CPU
            // and Memory usage).
            '*',
        ],

        'control' => [
            // Allows the user to send data to the server console process. A user with this
            // permission will not be able to stop the server directly by issuing the specified
            // stop command for the Egg, however depending on plugins and server configuration
            // they may still be able to control the server power state.
            'console', // power.send-command

            // Allows the user to start/stop/restart/kill the server process.
            'start', // power.power-start
            'stop', // power.power-stop
            'restart', // power.power-restart
            'kill', // power.power-kill
        ],

        'user' => [
            // Allows a user to create a new user assigned to the server. They will not be able
            // to assign any permissions they do not already have on their account as well.
            'create', // subuser.create-subuser
            'read', // subuser.list-subusers, subuser.view-subuser
            'update', // subuser.edit-subuser
            'delete', // subuser.delete-subuser
        ],

        'file' => [
            // Allows a user to create additional files and folders either via the Panel,
            // or via a direct upload.
            'create', // files.create-files, files.upload-files, files.copy-files, files.move-files

            // Allows a user to view the contents of a directory as well as read the contents
            // of a given file. A user with this permission will be able to download files
            // as well.
            'read', // files.list-files, files.download-files

            // Allows a user to update the contents of an existing file or directory.
            'update', // files.edit-files, files.save-files

            // Allows a user to delete a file or directory.
            'delete', // files.delete-files

            // Allows a user to archive the contents of a directory as well as decompress existing
            // archives on the system.
            'archive', // files.compress-files, files.decompress-files

            // Allows the user to connect and manage server files using their account
            // credentials and a SFTP client.
            'sftp', // files.access-sftp
        ],

        // Controls permissions for editing or viewing a server's allocations.
        'allocation' => [
            'read', // server.view-allocations
            'update', // server.edit-allocation
        ],

        // Controls permissions for editing or viewing a server's startup parameters.
        'startup' => [
            'read', // server.view-startup
            'update', // server.edit-startup
        ],

        'database' => [
            // Allows a user to create a new database for a server.
            'create', // database.create-database

            // Allows a user to view the databases associated with the server. If they do not also
            // have the view_password permission they will only be able to see the connection address
            // and the name of the user.
            'read', // database.view-databases

            // Allows a user to rotate the password on a database instance. If the user does not
            // alow have the view_password permission they will not be able to see the updated password
            // anywhere, but it will still be rotated.
            'update', // database.reset-db-password

            // Allows a user to delete a database instance.
            'delete', // database.delete-database

            // Allows a user to view the password associated with a database instance for the
            // server. Note that a user without this permission may still be able to access these
            // credentials by viewing files or the console.
            'view_password', // database.reset-db-password
        ],

        'schedule' => [
            'create', // task.create-schedule
            'read', // task.view-schedule, task.list-schedules
            'update', // task.edit-schedule, task.queue-schedule, task.toggle-schedule
            'delete', // task.delete-schedule
        ],
    ];

    /**
     * Returns all of the permissions available on the system for a user to
     * have when controlling a server.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function permissions(): Collection
    {
        return Collection::make(self::$permissions);
    }

    /**
     * A list of all permissions available for a user.
     *
     * @var array
     * @deprecated
     */
    protected static $deprecatedPermissions = [
        'power' => [
            'power-start' => 's:power:start',
            'power-stop' => 's:power:stop',
            'power-restart' => 's:power:restart',
            'power-kill' => 's:power:kill',
            'send-command' => 's:command',
        ],
        'subuser' => [
            'list-subusers' => null,
            'view-subuser' => null,
            'edit-subuser' => null,
            'create-subuser' => null,
            'delete-subuser' => null,
        ],
        'server' => [
            'view-allocations' => null,
            'edit-allocation' => null,
            'view-startup' => null,
            'edit-startup' => null,
        ],
        'database' => [
            'view-databases' => null,
            'reset-db-password' => null,
            'delete-database' => null,
            'create-database' => null,
        ],
        'file' => [
            'access-sftp' => null,
            'list-files' => 's:files:get',
            'edit-files' => 's:files:read',
            'save-files' => 's:files:post',
            'move-files' => 's:files:move',
            'copy-files' => 's:files:copy',
            'compress-files' => 's:files:compress',
            'decompress-files' => 's:files:decompress',
            'create-files' => 's:files:create',
            'upload-files' => 's:files:upload',
            'delete-files' => 's:files:delete',
            'download-files' => 's:files:download',
        ],
        'task' => [
            'list-schedules' => null,
            'view-schedule' => null,
            'toggle-schedule' => null,
            'queue-schedule' => null,
            'edit-schedule' => null,
            'create-schedule' => null,
            'delete-schedule' => null,
        ],
    ];

    /**
     * Return a collection of permissions available.
     *
     * @param bool $array
     * @return array|\Illuminate\Database\Eloquent\Collection
     * @deprecated
     */
    public static function getPermissions($array = false)
    {
        if ($array) {
            return collect(self::$deprecatedPermissions)->mapWithKeys(function ($item) {
                return $item;
            })->all();
        }

        return collect(self::$deprecatedPermissions);
    }

    /**
     * Find permission by permission node.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param string $permission
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopePermission($query, $permission)
    {
        return $query->where('permission', $permission);
    }

    /**
     * Filter permission by server.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \Pterodactyl\Models\Server $server
     * @return \Illuminate\Database\Query\Builder
     */
    public function scopeServer($query, Server $server)
    {
        return $query->where('server_id', $server->id);
    }
}
