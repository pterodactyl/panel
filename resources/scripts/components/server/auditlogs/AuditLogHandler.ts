import { ServerAuditLog } from '@/api/server/types';

export function AuditLogHandler (log: ServerAuditLog) {
    switch (log.action) {
        case 'server:backup.start':
            return `Created backup ${log.metadata.backup_name}`;
        case 'server:backup.fail':
            return `Backup ${log.metadata.backup_name} failed`;
        case 'server:backup.complete':
            return `Backup ${log.metadata.backup_name} completed`;
        case 'server:backup.delete':
            return `Deleted backup ${log.metadata.backup_name}`;
        case 'server:backup.download':
            return `Downloaded backup ${log.metadata.backup_name}`;
        case 'server:backup.lock':
            return `Locked backup ${log.metadata.backup_name}`;
        case 'server:backup.unlock':
            return `Unlocked backup ${log.metadata.backup_name}`;
        case 'server:backup.restore.start':
            return `Backup ${log.metadata.backup_name} restoration started`;
        case 'server:backup.restore.complete':
            return `Backup ${log.metadata.backup_name} restoration completed`;
        case 'server:backup.restore.fail':
            return `Backup ${log.metadata.backup_name} restoration failed`;

        case 'server:database.create':
            return `Created database ${log.metadata.database_name}`;
        case 'server:database.password.rotate':
            return `Rotated password of database ${log.metadata.database_name}`;
        case 'server:database.delete':
            return `Deleted database ${log.metadata.database_name}`;

        case 'server:filesystem.download':
            return `Downloaded file ${log.metadata.file}`;
        case 'server:filesystem.write':
            return `Edited file ${log.metadata.file}`;
        case 'server:filesystem.delete':
            if (log.metadata.files.length > 1) {
                return `Deleted files ${log.metadata.files.join(', ')}`;
            } else {
                return `Deleted file ${log.metadata.files[0]}`;
            }
        case 'server:filesystem.rename':
            return `Renamed file ${log.metadata.files[0].from} to ${log.metadata.files[0].to}`;
        case 'server:filesystem.compress':
            if (log.metadata.files.length > 1) {
                return `Compressed files ${log.metadata.files.join(', ')} in ${log.metadata.root}`;
            } else {
                return `Compressed file ${log.metadata.root+log.metadata.files[0]}`;
            }
        case 'server:filesystem.decompress':
            return `Decompressed file ${log.metadata.root+log.metadata.files}`;
        case 'server:filesystem.pull':
            return `URL ${log.metadata.url} pulled into ${log.metadata.directory}`;

        case 'server:allocation.set.primary':
            return `Set allocation port ${log.metadata.allocation_port} as primary`;
        case 'server:allocation.delete':
            return `Deleted allocation port ${log.metadata.allocation_port}`;
        case 'server:allocation.create':
            return `Created allocation port ${log.metadata.allocation_port}`;

        case 'server:schedule.create':
            return `Created schedule ${log.metadata.schedule_name}`;
        case 'server:schedule.update':
            return `Updated schedule ${log.metadata.schedule_name}`;
        case 'server:schedule.delete':
            return `Deleted schedule ${log.metadata.schedule_name}`;
        case 'server:schedule.run':
            return `Executed schedule ${log.metadata.schedule_name}`;
        case 'server:schedule.task.create':
            return `Created task inside schedule ${log.metadata.schedule_name}`;
        case 'server:schedule.task.update':
            return `Updated task inside schedule ${log.metadata.schedule_name}`;
        case 'server:schedule.task.delete':
            return `Deleted task inside schedule ${log.metadata.schedule_name}`;

        case 'server:settings.name.update':
            return `Renamed server from ${log.metadata.old} to ${log.metadata.new}`;
        case 'server:settings.reinstall':
            return `Reinstalled server`;
        case 'server:settings.image.update':
            return `Updated Docker image from ${log.metadata.old} to ${log.metadata.new}`;

        case 'server:subuser.create':
            return `Created subuser ${log.metadata.user}`;
        case 'server:subuser.update':
            return `Updated subuser ${log.metadata.user}`;
        case 'server:subuser.delete':
            return `Deleted subuser ${log.metadata.user}`;

        default:
            return 'Log type not matching any known types';
    }
}
