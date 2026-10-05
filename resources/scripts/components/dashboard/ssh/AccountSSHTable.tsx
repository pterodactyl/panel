import type { ColumnDef } from '@tanstack/react-table';
import type { SSHKey } from '@/api/account/ssh-keys/queries';
import DeleteSSHKeyButton from '@/components/dashboard/ssh/DeleteSSHKeyButton';
import dayjs from '@/lib/dayjs';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import { actionsColumn, RowActions } from '@/components/elements/table/RowActions';

export const accountSshColumns: ColumnDef<SSHKey>[] = [
    {
        id: 'name',
        accessorFn: (key) => key.attributes.name,
        header: ({ column }) => <DataTableColumnHeader column={column} title={'Name'} />,
        cell: ({ row }) => (
            <div className={'min-w-40'}>
                <p className={'truncate font-medium'}>{row.original.attributes.name}</p>
                <p className={'mt-0.5 truncate font-mono text-xs text-muted-foreground md:hidden'}>
                    SHA256:{row.original.attributes.fingerprint}
                </p>
            </div>
        ),
    },
    {
        id: 'fingerprint',
        accessorFn: (key) => key.attributes.fingerprint,
        header: ({ column }) => <DataTableColumnHeader column={column} title={'Fingerprint'} />,
        cell: ({ row }) => (
            <code className={'font-mono text-xs text-muted-foreground'}>
                SHA256:{row.original.attributes.fingerprint}
            </code>
        ),
        meta: { headerClassName: 'hidden md:table-cell', cellClassName: 'hidden md:table-cell' },
    },
    {
        id: 'created_at',
        accessorFn: (key) => key.attributes.created_at,
        header: ({ column }) => <DataTableColumnHeader column={column} title={'Added'} />,
        cell: ({ row }) => (
            <time
                dateTime={row.original.attributes.created_at}
                className={'whitespace-nowrap text-xs text-muted-foreground'}
            >
                {dayjs(row.original.attributes.created_at).format('MMM D, YYYY HH:mm')}
            </time>
        ),
        meta: { headerClassName: 'hidden lg:table-cell w-40', cellClassName: 'hidden lg:table-cell w-40' },
    },
    actionsColumn<SSHKey>(1, (key) => (
        <RowActions>
            <DeleteSSHKeyButton name={key.attributes.name} fingerprint={key.attributes.fingerprint} />
        </RowActions>
    )),
];
