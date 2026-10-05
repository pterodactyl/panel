<?php

declare(strict_types=1);

namespace Pterodactyl\Enum;

/**
 * Fine-grained permission keys declared by admin API requests. Enforcement is currently
 * gated on the root administrator flag (see AuthServiceProvider), so these act as
 * self-documenting, forward-compatible labels for future roles.
 */
enum Permissions: string
{
    case AdminUsersRead = 'admin:users.read';
    case AdminUsersCreate = 'admin:users.create';
    case AdminUsersUpdate = 'admin:users.update';
    case AdminUsersDelete = 'admin:users.delete';

    case AdminNodesRead = 'admin:nodes.read';
    case AdminNodesCreate = 'admin:nodes.create';
    case AdminNodesUpdate = 'admin:nodes.update';
    case AdminNodesDelete = 'admin:nodes.delete';

    case AdminAllocationsRead = 'admin:allocations.read';
    case AdminAllocationsCreate = 'admin:allocations.create';
    case AdminAllocationsUpdate = 'admin:allocations.update';
    case AdminAllocationsDelete = 'admin:allocations.delete';

    case AdminLocationsRead = 'admin:locations.read';
    case AdminLocationsCreate = 'admin:locations.create';
    case AdminLocationsUpdate = 'admin:locations.update';
    case AdminLocationsDelete = 'admin:locations.delete';

    case AdminMountsRead = 'admin:mounts.read';
    case AdminMountsCreate = 'admin:mounts.create';
    case AdminMountsUpdate = 'admin:mounts.update';
    case AdminMountsDelete = 'admin:mounts.delete';

    case AdminTagsRead = 'admin:tags.read';
    case AdminTagsCreate = 'admin:tags.create';
    case AdminTagsUpdate = 'admin:tags.update';
    case AdminTagsDelete = 'admin:tags.delete';

    case AdminEggsRead = 'admin:eggs.read';
    case AdminEggsCreate = 'admin:eggs.create';
    case AdminEggsUpdate = 'admin:eggs.update';
    case AdminEggsDelete = 'admin:eggs.delete';

    case AdminEggVariablesRead = 'admin:egg-variables.read';
    case AdminEggVariablesCreate = 'admin:egg-variables.create';
    case AdminEggVariablesUpdate = 'admin:egg-variables.update';
    case AdminEggVariablesDelete = 'admin:egg-variables.delete';

    case AdminServersRead = 'admin:servers.read';
    case AdminServersCreate = 'admin:servers.create';
    case AdminServersUpdate = 'admin:servers.update';
    case AdminServersDelete = 'admin:servers.delete';

    case AdminServerDatabasesRead = 'admin:server-databases.read';
    case AdminServerDatabasesCreate = 'admin:server-databases.create';
    case AdminServerDatabasesUpdate = 'admin:server-databases.update';
    case AdminServerDatabasesDelete = 'admin:server-databases.delete';

    case AdminServerMountsRead = 'admin:server-mounts.read';
    case AdminServerMountsCreate = 'admin:server-mounts.create';
    case AdminServerMountsDelete = 'admin:server-mounts.delete';

    case AdminServerBackupsRead = 'admin:server-backups.read';
    case AdminServerBackupsUpdate = 'admin:server-backups.update';

    case AdminSettingsRead = 'admin:settings.read';
    case AdminSettingsUpdate = 'admin:settings.update';

    case AdminExtensionsRead = 'admin:extensions.read';
    case AdminExtensionsInstall = 'admin:extensions.install';
    case AdminExtensionsUpdate = 'admin:extensions.update';
    case AdminExtensionsDelete = 'admin:extensions.delete';

    case AdminDatabaseHostsRead = 'admin:database-hosts.read';
    case AdminDatabaseHostsCreate = 'admin:database-hosts.create';
    case AdminDatabaseHostsUpdate = 'admin:database-hosts.update';
    case AdminDatabaseHostsDelete = 'admin:database-hosts.delete';

    case AdminApiKeysRead = 'admin:api-keys.read';
    case AdminApiKeysCreate = 'admin:api-keys.create';
    case AdminApiKeysUpdate = 'admin:api-keys.update';
    case AdminApiKeysDelete = 'admin:api-keys.delete';

    case AdminVersionRead = 'admin:version.read';

    case AdminActivityRead = 'admin:activity.read';

    // Server subuser permissions, granted per-server and checked via ServerPolicy.
    case WebsocketConnect = 'websocket.connect';

    case ControlConsole = 'control.console';
    case ControlStart = 'control.start';
    case ControlStop = 'control.stop';
    case ControlRestart = 'control.restart';

    case DatabaseRead = 'database.read';
    case DatabaseCreate = 'database.create';
    case DatabaseUpdate = 'database.update';
    case DatabaseDelete = 'database.delete';
    case DatabaseViewPassword = 'database.view_password';

    case ScheduleRead = 'schedule.read';
    case ScheduleCreate = 'schedule.create';
    case ScheduleUpdate = 'schedule.update';
    case ScheduleDelete = 'schedule.delete';

    case UserRead = 'user.read';
    case UserCreate = 'user.create';
    case UserUpdate = 'user.update';
    case UserDelete = 'user.delete';

    case BackupRead = 'backup.read';
    case BackupCreate = 'backup.create';
    case BackupDelete = 'backup.delete';
    case BackupDownload = 'backup.download';
    case BackupRestore = 'backup.restore';

    case AllocationRead = 'allocation.read';
    case AllocationCreate = 'allocation.create';
    case AllocationUpdate = 'allocation.update';
    case AllocationDelete = 'allocation.delete';

    case FileRead = 'file.read';
    case FileReadContent = 'file.read-content';
    case FileCreate = 'file.create';
    case FileUpdate = 'file.update';
    case FileDelete = 'file.delete';
    case FileArchive = 'file.archive';
    case FileSftp = 'file.sftp';

    case StartupRead = 'startup.read';
    case StartupUpdate = 'startup.update';
    case StartupDockerImage = 'startup.docker-image';

    case SettingsRename = 'settings.rename';
    case SettingsReinstall = 'settings.reinstall';

    case ActivityRead = 'activity.read';
}
