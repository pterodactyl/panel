import type { ColumnDef } from '@tanstack/react-table';
import type { ApiKey } from '@/api/account/api-keys/queries';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import { actionsColumn, DeleteAction, RowActions } from '@/components/elements/table/RowActions';
import dayjs from '@/lib/dayjs';

export const accountApiColumns = (onDelete: (identifier: string) => void): ColumnDef<ApiKey>[] => [
    {
        id: 'description',
        accessorFn: (key) => key.attributes.description,
        header: ({ column }) => <DataTableColumnHeader column={column} title='Description' />,
        cell: ({ row }) => (
            <div className='min-w-40'>
                <p className='truncate font-medium'>{row.original.attributes.description}</p>
                <p className='mt-0.5 truncate font-mono text-xs text-muted-foreground md:hidden'>
                    {row.original.attributes.identifier}
                </p>
            </div>
        ),
    },
    {
        id: 'identifier',
        accessorFn: (key) => key.attributes.identifier,
        header: ({ column }) => <DataTableColumnHeader column={column} title='Identifier' />,
        cell: ({ row }) => (
            <code className='font-mono text-xs text-muted-foreground'>{row.original.attributes.identifier}</code>
        ),
        meta: { headerClassName: 'hidden md:table-cell', cellClassName: 'hidden md:table-cell' },
    },
    {
        id: 'last_used_at',
        accessorFn: (key) => key.attributes.last_used_at,
        header: ({ column }) => <DataTableColumnHeader column={column} title='Last used' />,
        cell: ({ row }) => (
            <span className='whitespace-nowrap text-xs text-muted-foreground'>
                {row.original.attributes.last_used_at
                    ? dayjs(row.original.attributes.last_used_at).format('MMM D, YYYY HH:mm')
                    : 'Never'}
            </span>
        ),
        meta: { headerClassName: 'hidden lg:table-cell w-40', cellClassName: 'hidden lg:table-cell w-40' },
    },
    actionsColumn<ApiKey>(1, (key) => (
        <RowActions>
            <DeleteAction
                aria-label={`Delete ${key.attributes.description} API key`}
                onClick={() => onDelete(key.attributes.identifier)}
            />
        </RowActions>
    )),
];
