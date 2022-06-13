<?php

namespace Pterodactyl\Models;

use Illuminate\Support\Collection;

class Permission extends Model
{
    /**
     * The resource name for this model when it is transformed into an
     * API representation using fractal.
     */
    public const RESOURCE_NAME = 'subuser_permission';

    /**
     * Constants defining different permissions available.
     */
    public const ACTION_WEBSOCKET_CONNECT = 'websocket.connect';
    public const ACTION_CONTROL_CONSOLE = 'control.console';
    public const ACTION_CONTROL_START = 'control.start';
    public const ACTION_CONTROL_STOP = 'control.stop';
    public const ACTION_CONTROL_RESTART = 'control.restart';

    public const ACTION_DATABASE_READ = 'database.read';
    public const ACTION_DATABASE_CREATE = 'database.create';
    public const ACTION_DATABASE_UPDATE = 'database.update';
    public const ACTION_DATABASE_DELETE = 'database.delete';
    public const ACTION_DATABASE_VIEW_PASSWORD = 'database.view_password';

    public const ACTION_SCHEDULE_READ = 'schedule.read';
    public const ACTION_SCHEDULE_CREATE = 'schedule.create';
    public const ACTION_SCHEDULE_UPDATE = 'schedule.update';
    public const ACTION_SCHEDULE_DELETE = 'schedule.delete';

    public const ACTION_USER_READ = 'user.read';
    public const ACTION_USER_CREATE = 'user.create';
    public const ACTION_USER_UPDATE = 'user.update';
    public const ACTION_USER_DELETE = 'user.delete';

    public const ACTION_BACKUP_READ = 'backup.read';
    public const ACTION_BACKUP_CREATE = 'backup.create';
    public const ACTION_BACKUP_DELETE = 'backup.delete';
    public const ACTION_BACKUP_DOWNLOAD = 'backup.download';
    public const ACTION_BACKUP_RESTORE = 'backup.restore';

    public const ACTION_ALLOCATION_READ = 'allocation.read';
    public const ACTION_ALLOCATION_CREATE = 'allocation.create';
    public const ACTION_ALLOCATION_UPDATE = 'allocation.update';
    public const ACTION_ALLOCATION_DELETE = 'allocation.delete';

    public const ACTION_FILE_READ = 'file.read';
    public const ACTION_FILE_READ_CONTENT = 'file.read-content';
    public const ACTION_FILE_CREATE = 'file.create';
    public const ACTION_FILE_UPDATE = 'file.update';
    public const ACTION_FILE_DELETE = 'file.delete';
    public const ACTION_FILE_ARCHIVE = 'file.archive';
    public const ACTION_FILE_SFTP = 'file.sftp';

    public const ACTION_STARTUP_READ = 'startup.read';
    public const ACTION_STARTUP_UPDATE = 'startup.update';
    public const ACTION_STARTUP_DOCKER_IMAGE = 'startup.docker-image';

    public const ACTION_SETTINGS_RENAME = 'settings.rename';
    public const ACTION_SETTINGS_REINSTALL = 'settings.reinstall';

    public const ACTION_ACTIVITY_READ = 'activity.read';

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
     *
     * @see \Pterodactyl\Models\Permission::permissions()
     */
    protected static $permissions = [
        'websocket' => [
            'description' => '允许用户连接到服务器 websocket，让他们可以访问查看控制台输出和实时服务器统计信息。',
            'keys' => [
                'connect' => '允许用户连接到服务器的 websocket 实例以流式传输控制台。',
            ],
        ],

        'control' => [
            'description' => '控制用户控制服务器电源状态或发送命令的能力的权限。',
            'keys' => [
                'console' => '允许用户通过控制台向服务器实例发送命令。',
                'start' => '允许用户在服务器停止时启动它。',
                'stop' => '允许用户停止正在运行的服务器。',
                'restart' => '允许用户执行服务器重启。 这允许他们在脱机时启动服务器，但不会将服务器置于完全停止状态。',
            ],
        ],

        'user' => [
            'description' => '允许用户管理服务器上其他子用户的权限。 他们将永远无法编辑自己的帐户，或分配他们自己没有的权限。',
            'keys' => [
                'create' => '允许用户为服务器创建新的子用户。',
                'read' => '允许用户查看子用户及其对服务器的权限。',
                'update' => '允许用户修改其他子用户。',
                'delete' => '允许用户从服务器中删除子用户。',
            ],
        ],

        'file' => [
            'description' => '控制用户修改此服务器文件系统能力的权限。',
            'keys' => [
                'create' => '允许用户通过面板或直接上传创建其他文件和文件夹。',
                'read' => '允许用户查看目录的内容，但不能查看或下载文件的内容。',
                'read-content' => '允许用户查看给定文件的内容。 这也将允许用户下载文件。',
                'update' => '允许用户更新现有文件或目录的内容。',
                'delete' => '允许用户删除文件或目录。',
                'archive' => '允许用户压缩系统上的的文件以及解压系统上的现有压缩文件。',
                'sftp' => '允许用户使用其他分配的文件权限连接到 SFTP 并管理服务器文件。',
            ],
        ],

        'backup' => [
            'description' => '控制用户创建和管理服务器备份的能力的权限。',
            'keys' => [
                'create' => '允许用户为此服务器创建新备份。',
                'read' => '允许用户查看此服务器存在的所有备份。',
                'delete' => '允许用户从系统中删除备份。',
                'download' => '允许用户下载服务器的备份。 这是个危险权限：这允许用户访问备份中服务器的所有文件。',
                'restore' => '允许用户恢复服务器的备份。 这是个危险权限：这允许用户删除服务器实例中的所有服务器文件。',
            ],
        ],

        // Controls permissions for editing or viewing a server's allocations.
        'allocation' => [
            'description' => '控制用户修改此服务器端口分配能力的权限。',
            'keys' => [
                'read' => '允许用户查看当前分配给该服务器的所有分配。 对该服务器具有任何访问级别的用户始终可以查看主要分配。',
                'create' => '允许用户向服务器创建额外的分配。',
                'update' => '允许用户更改主服务器分配并将注释附加到每个分配。',
                'delete' => '允许用户从服务器中删除分配。',
            ],
        ],

        // Controls permissions for editing or viewing a server's startup parameters.
        'startup' => [
            'description' => '控制用户查看此服务器启动参数的能力的权限。',
            'keys' => [
                'read' => '允许用户查看服务器的启动变量。',
                'update' => '允许用户修改服务器的启动变量。',
                'docker-image' => '允许用户修改运行服务器时使用的 Docker 镜像。',
            ],
        ],

        'database' => [
            'description' => '控制用户访问此服务器的数据库管理的权限。',
            'keys' => [
                'create' => '允许用户为此服务器创建新数据库。',
                'read' => '允许用户查看与此服务器关联的数据库。',
                'update' => '允许用户重置数据库实例的密码。 如果用户没有 view_password 权限，他们将看不到更新的密码。',
                'delete' => '允许用户从此服务器中删除数据库实例。',
                'view_password' => '允许用户查看与此服务器的数据库实例关联的密码。',
            ],
        ],

        'schedule' => [
            'description' => '控制用户访问此服务器计划系统的权限。',
            'keys' => [
                'create' => '允许用户为此服务器创建新计划。', // task.create-schedule
                'read' => '允许用户查看此服务器的计划和与其关联的任务。', // task.view-schedule, task.list-schedules
                'update' => '允许用户更新此服务器的计划和计划中的任务。', // task.edit-schedule, task.queue-schedule, task.toggle-schedule
                'delete' => '允许用户删除此服务器的计划。', // task.delete-schedule
            ],
        ],

        'settings' => [
            'description' => '控制用户访问此服务器设置的权限。',
            'keys' => [
                'rename' => '允许用户重命名此服务器。',
                'reinstall' => '允许用户执行此服务器的重新安装程序。',
            ],
        ],

        'activity' => [
            'description' => 'Permissions that control a user\'s access to the server activity logs.',
            'keys' => [
                'read' => 'Allows a user to view the activity logs for the server.',
            ],
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
}
