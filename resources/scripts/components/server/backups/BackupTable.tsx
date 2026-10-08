import type { ColumnDef } from '@tanstack/react-table';
import { Archive, Lock } from 'lucide-react';
import type { ServerBackup } from '@/api/server/backups/queries';
import Can from '@/components/elements/Can';
import Icon from '@/components/elements/Icon';
import Spinner from '@/components/elements/Spinner';
import { actionsColumn } from '@/components/elements/table/RowActions';
import BackupContextMenu from '@/components/server/backups/BackupContextMenu';
import { bytesToString } from '@/lib/formatters';
import dayjs from '@/lib/dayjs';

const BackupIdentityCell = ({ backup }: { backup: ServerBackup }) => {
    const { attributes } = backup;

    return (
        <div className='flex min-w-44 items-center gap-3'>
            {attributes.completed_at === null ? (
                <Spinner size='small' />
            ) : (
                <Icon
                    icon={attributes.is_locked ? Lock : Archive}
                    className={attributes.is_locked ? 'text-warning' : 'text-muted-foreground'}
                />
            )}
            <div className='min-w-0'>
                <div className='flex items-center gap-2'>
                    {attributes.completed_at !== null && !attributes.is_successful ? (
                        <span className='rounded-sm bg-destructive px-1.5 py-0.5 text-xs text-destructive-foreground'>
                            Failed
                        </span>
                    ) : null}
                    <p className='truncate font-medium'>{attributes.name}</p>
                </div>
                <p className='mt-0.5 truncate font-mono text-xs text-muted-foreground'>{attributes.checksum}</p>
            </div>
        </div>
    );
};

const BackupActionsCell = ({ backup, page }: { backup: ServerBackup; page: number }) => (
    <Can action={['backup.download', 'backup.restore', 'backup.delete']} matchAny>
        {backup.attributes.completed_at ? <BackupContextMenu backup={backup} page={page} /> : null}
    </Can>
);

export const backupColumns = (page: number): ColumnDef<ServerBackup>[] => [
    {
        id: 'name',
        accessorFn: (backup) => backup.attributes.name,
        header: 'Backup',
        cell: ({ row }) => <BackupIdentityCell backup={row.original} />,
        enableSorting: false,
    },
    {
        id: 'bytes',
        accessorFn: (backup) => backup.attributes.bytes,
        header: 'Size',
        cell: ({ row }) => (
            <span className='whitespace-nowrap text-xs'>
                {row.original.attributes.completed_at && row.original.attributes.is_successful
                    ? bytesToString(row.original.attributes.bytes)
                    : '-'}
            </span>
        ),
        enableSorting: false,
        meta: { headerClassName: 'hidden md:table-cell w-28', cellClassName: 'hidden md:table-cell w-28' },
    },
    {
        id: 'created_at',
        accessorFn: (backup) => backup.attributes.created_at,
        header: 'Created',
        cell: ({ row }) => (
            <time
                dateTime={row.original.attributes.created_at}
                title={dayjs(row.original.attributes.created_at).format('ddd, MMMM D, YYYY HH:mm:ss')}
                className='whitespace-nowrap text-xs text-muted-foreground'
            >
                {dayjs(row.original.attributes.created_at).fromNow()}
            </time>
        ),
        enableSorting: false,
        meta: { headerClassName: 'hidden sm:table-cell w-32', cellClassName: 'hidden sm:table-cell w-32' },
    },
    actionsColumn<ServerBackup>(2, (backup) => <BackupActionsCell backup={backup} page={page} />),
];
