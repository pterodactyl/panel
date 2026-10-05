<?php

declare(strict_types=1);

namespace Pterodactyl\Models;

use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Support\Collection;

#[Guarded(['id', 'created_at', 'updated_at'])]
#[WithoutTimestamps]
class Permission extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const string RESOURCE_NAME = 'subuser_permission';

    /**
     * All the permissions available on the system. You should use self::permissions()
     * to retrieve them, and not directly access this array as it is subject to change.
     *
     * @see Permission::permissions()
     *
     * @var array<string, array{description: string, keys: array<string, string>}>
     */
    protected static array $permissions = [
        'websocket' => [
            'description' => 'Allows the user to connect to the server websocket, giving them access to view console output and realtime server stats.',
            'keys' => [
                'connect' => 'Allows a user to connect to the websocket instance for a server to stream the console.',
            ],
        ],

        'control' => [
            'description' => "Permissions that control a user's ability to control the power state of a server, or send commands.",
            'keys' => [
                'console' => 'Allows a user to send commands to the server instance via the console.',
                'start' => 'Allows a user to start the server if it is stopped.',
                'stop' => 'Allows a user to stop a server if it is running.',
                'restart' => 'Allows a user to perform a server restart. This allows them to start the server if it is offline, but not put the server in a completely stopped state.',
            ],
        ],

        'user' => [
            'description' => 'Permissions that allow a user to manage other subusers on a server. They will never be able to edit their own account, or assign permissions they do not have themselves.',
            'keys' => [
                'create' => 'Allows a user to create new subusers for the server.',
                'read' => 'Allows the user to view subusers and their permissions for the server.',
                'update' => 'Allows a user to modify other subusers.',
                'delete' => 'Allows a user to delete a subuser from the server.',
            ],
        ],

        'file' => [
            'description' => "Permissions that control a user's ability to modify the filesystem for this server.",
            'keys' => [
                'create' => 'Allows a user to create additional files and folders via the Panel or direct upload.',
                'read' => 'Allows a user to view the contents of a directory, but not view the contents of or download files.',
                'read-content' => 'Allows a user to view the contents of a given file. This will also allow the user to download files.',
                'update' => 'Allows a user to update the contents of an existing file or directory.',
                'delete' => 'Allows a user to delete files or directories.',
                'archive' => 'Allows a user to archive the contents of a directory as well as decompress existing archives on the system.',
                'sftp' => 'Allows a user to connect to SFTP and manage server files using the other assigned file permissions.',
            ],
        ],

        'backup' => [
            'description' => "Permissions that control a user's ability to generate and manage server backups.",
            'keys' => [
                'create' => 'Allows a user to create new backups for this server.',
                'read' => 'Allows a user to view all backups that exist for this server.',
                'delete' => 'Allows a user to remove backups from the system.',
                'download' => 'Allows a user to download a backup for the server. Danger: this allows a user to access all files for the server in the backup.',
                'restore' => 'Allows a user to restore a backup for the server. Danger: this allows the user to delete all of the server files in the process.',
            ],
        ],

        // Controls permissions for editing or viewing a server's allocations.
        'allocation' => [
            'description' => "Permissions that control a user's ability to modify the port allocations for this server.",
            'keys' => [
                'read' => 'Allows a user to view all allocations currently assigned to this server. Users with any level of access to this server can always view the primary allocation.',
                'create' => 'Allows a user to assign additional allocations to the server.',
                'update' => 'Allows a user to change the primary server allocation and attach notes to each allocation.',
                'delete' => 'Allows a user to delete an allocation from the server.',
            ],
        ],

        // Controls permissions for editing or viewing a server's startup parameters.
        'startup' => [
            'description' => "Permissions that control a user's ability to view this server's startup parameters.",
            'keys' => [
                'read' => 'Allows a user to view the startup variables for a server.',
                'update' => 'Allows a user to modify the startup variables for the server.',
                'docker-image' => 'Allows a user to modify the Docker image used when running the server.',
            ],
        ],

        'database' => [
            'description' => "Permissions that control a user's access to the database management for this server.",
            'keys' => [
                'create' => 'Allows a user to create a new database for this server.',
                'read' => 'Allows a user to view the database associated with this server.',
                'update' => 'Allows a user to rotate the password on a database instance. If the user does not have the view_password permission they will not see the updated password.',
                'delete' => 'Allows a user to remove a database instance from this server.',
                'view_password' => 'Allows a user to view the password associated with a database instance for this server.',
            ],
        ],

        'schedule' => [
            'description' => "Permissions that control a user's access to the schedule management for this server.",
            'keys' => [
                'create' => 'Allows a user to create new schedules for this server.', // task.create-schedule
                'read' => 'Allows a user to view schedules and the tasks associated with them for this server.', // task.view-schedule, task.list-schedules
                'update' => 'Allows a user to update schedules and schedule tasks for this server.', // task.edit-schedule, task.queue-schedule, task.toggle-schedule
                'delete' => 'Allows a user to delete schedules for this server.', // task.delete-schedule
            ],
        ],

        'settings' => [
            'description' => "Permissions that control a user's access to the settings for this server.",
            'keys' => [
                'rename' => 'Allows a user to rename this server and change the description of it.',
                'reinstall' => 'Allows a user to trigger a reinstall of this server.',
            ],
        ],

        'activity' => [
            'description' => "Permissions that control a user's access to the server activity logs.",
            'keys' => [
                'read' => 'Allows a user to view the activity logs for the server.',
            ],
        ],
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'subuser_id' => 'integer',
        ];
    }

    /**
     * Returns all the permissions available on the system for a user to
     * have when controlling a server.
     *
     * @return Collection<string, array{description: string, keys: array<string, string>}>
     */
    public static function permissions(): Collection
    {
        return Collection::make(self::$permissions);
    }
}
