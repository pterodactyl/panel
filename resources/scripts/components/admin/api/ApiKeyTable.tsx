import type { ColumnDef } from '@tanstack/react-table';
import { Link } from '@tanstack/react-router';
import {
    API_KEY_RESOURCES,
    deleteAdminApiKeyInput,
    permissionValue,
    type AdminApiKey,
    useDeleteAdminApiKey,
} from '@/api/admin/api-keys/queries';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import { actionsColumn, DeleteAction, RowActions } from '@/components/elements/table/RowActions';
import dayjs from '@/lib/dayjs';

const grantedCount = (apiKey: AdminApiKey): number =>
    API_KEY_RESOURCES.filter((resource) => permissionValue(apiKey, resource) > 0).length;

const ApiKeyActionsCell = ({ apiKey }: { apiKey: AdminApiKey }) => {
    const deleteApiKey = useDeleteAdminApiKey();
    const isSubmitting = deleteApiKey.isPending;

    return (
        <RowActions>
            <Dialog.ConfirmTrigger
                title={'Confirm key deletion'}
                confirm={'Delete key'}
                preventExternalClose={isSubmitting}
                hideCloseIcon={isSubmitting}
                pending={isSubmitting}
                trigger={({ onClick }) => (
                    <DeleteAction aria-label={`Delete ${apiKey.attributes.identifier}`} onClick={onClick} />
                )}
                onConfirmed={(_event, close) =>
                    deleteApiKey.mutate(deleteAdminApiKeyInput(apiKey.attributes.identifier), { onSuccess: close })
                }
            >
                <SpinnerOverlay visible={isSubmitting} />
                Deleting this application API key is permanent. Integrations using{' '}
                <strong>{apiKey.attributes.identifier}</strong> will immediately stop working.
            </Dialog.ConfirmTrigger>
        </RowActions>
    );
};

export const apiKeyColumns = [
    {
        id: 'memo',
        accessorFn: (apiKey) => apiKey.attributes.memo,
        header: ({ column }) => <DataTableColumnHeader column={column} title={'Description'} />,
        cell: ({ row }) => (
            <div className={'w-0 min-w-full'}>
                <p className={'truncate font-medium'} title={row.original.attributes.memo || undefined}>
                    {row.original.attributes.memo || 'No description provided'}
                </p>
                <p className={'mt-0.5 truncate font-mono text-xs text-muted-foreground'}>
                    {row.original.attributes.identifier}
                </p>
            </div>
        ),
        enableSorting: true,
        meta: { headerClassName: 'min-w-48', cellClassName: 'min-w-48' },
    },
    {
        id: 'permissions',
        header: 'Permissions',
        cell: ({ row }) => <span className={'whitespace-nowrap text-xs'}>{grantedCount(row.original)} granted</span>,
        enableSorting: false,
        meta: { headerClassName: 'hidden w-28 @md:table-cell', cellClassName: 'hidden w-28 @md:table-cell' },
    },
    {
        id: 'created_by',
        header: 'Created by',
        cell: ({ row }) => {
            const createdBy = row.original.attributes.created_by;
            return createdBy ? (
                <Link
                    to={'/panel/users/$id'}
                    params={{ id: createdBy.id }}
                    title={createdBy.username}
                    className={'block w-0 min-w-full truncate text-foreground no-underline hover:text-accent'}
                >
                    {createdBy.username}
                </Link>
            ) : (
                <span className={'text-muted-foreground'}>Unknown</span>
            );
        },
        enableSorting: false,
        meta: { headerClassName: 'hidden w-32 @xl:table-cell', cellClassName: 'hidden w-32 @xl:table-cell' },
    },
    {
        id: 'last_used_at',
        header: 'Last used',
        cell: ({ row }) => {
            const lastUsedAt = row.original.attributes.last_used_at;
            return (
                <span className={'whitespace-nowrap text-xs text-muted-foreground'}>
                    {lastUsedAt ? dayjs(lastUsedAt).format('MMM D, YYYY') : 'Never'}
                </span>
            );
        },
        enableSorting: false,
        meta: { headerClassName: 'hidden w-28 @2xl:table-cell', cellClassName: 'hidden w-28 @2xl:table-cell' },
    },
    {
        id: 'created_at',
        accessorFn: (apiKey) => apiKey.attributes.created_at,
        header: ({ column }) => <DataTableColumnHeader column={column} title={'Created'} />,
        cell: ({ row }) => (
            <time
                className={'whitespace-nowrap text-xs text-muted-foreground'}
                dateTime={row.original.attributes.created_at}
            >
                {dayjs(row.original.attributes.created_at).format('MMM D, YYYY')}
            </time>
        ),
        enableSorting: true,
        sortDescFirst: true,
        meta: { headerClassName: 'hidden w-28 @3xl:table-cell', cellClassName: 'hidden w-28 @3xl:table-cell' },
    },
    actionsColumn<AdminApiKey>(1, (apiKey) => <ApiKeyActionsCell apiKey={apiKey} />),
] satisfies ColumnDef<AdminApiKey>[];
