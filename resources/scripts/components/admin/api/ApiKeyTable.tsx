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
import Translate from '@/components/elements/Translate';
import { useTranslation, type Translation } from '@/sdk/localization';

const grantedCount = (apiKey: AdminApiKey): number =>
    API_KEY_RESOURCES.filter((resource) => permissionValue(apiKey, resource) > 0).length;

const ApiKeyActionsCell = ({ apiKey }: { apiKey: AdminApiKey }) => {
    const { t } = useTranslation();
    const deleteApiKey = useDeleteAdminApiKey();
    const isSubmitting = deleteApiKey.isPending;

    return (
        <RowActions>
            <Dialog.ConfirmTrigger
                title={t('Confirm key deletion')}
                confirm={t('Delete key')}
                preventExternalClose={isSubmitting}
                hideCloseIcon={isSubmitting}
                pending={isSubmitting}
                trigger={({ onClick }) => (
                    <DeleteAction
                        aria-label={t('Delete {{name}}', { name: apiKey.attributes.identifier })}
                        onClick={onClick}
                    />
                )}
                onConfirmed={(_event, close) =>
                    deleteApiKey.mutate(deleteAdminApiKeyInput(apiKey.attributes.identifier), { onSuccess: close })
                }
            >
                <SpinnerOverlay visible={isSubmitting} />
                <Translate
                    i18nKey='Deleting this application API key is permanent. Integrations using <bold>{{identifier}}</bold> will immediately stop working.'
                    values={{ identifier: apiKey.attributes.identifier }}
                    components={{ bold: <strong /> }}
                />
            </Dialog.ConfirmTrigger>
        </RowActions>
    );
};

export const apiKeyColumns = (t: Translation['t']) =>
    [
        {
            id: 'memo',
            accessorFn: (apiKey) => apiKey.attributes.memo,
            header: ({ column }) => <DataTableColumnHeader column={column} title={t('Description')} />,
            cell: ({ row }) => (
                <div className='w-0 min-w-full'>
                    <p className='truncate font-medium' title={row.original.attributes.memo || undefined}>
                        {row.original.attributes.memo || t('No description provided')}
                    </p>
                    <p className='mt-0.5 truncate font-mono text-xs text-muted-foreground'>
                        {row.original.attributes.identifier}
                    </p>
                </div>
            ),
            enableSorting: true,
            meta: { headerClassName: 'min-w-48', cellClassName: 'min-w-48' },
        },
        {
            id: 'permissions',
            header: t('Permissions'),
            cell: ({ row }) => (
                <span className='whitespace-nowrap text-xs'>
                    {t('{{total}} granted', { total: grantedCount(row.original) })}
                </span>
            ),
            enableSorting: false,
            meta: { headerClassName: 'hidden w-28 @md:table-cell', cellClassName: 'hidden w-28 @md:table-cell' },
        },
        {
            id: 'created_by',
            header: t('Created by'),
            cell: ({ row }) => {
                const createdBy = row.original.attributes.created_by;

                return createdBy ? (
                    <Link
                        to='/panel/users/$id'
                        params={{ id: createdBy.id }}
                        title={createdBy.username}
                        className='block w-0 min-w-full truncate text-foreground no-underline hover:text-accent'
                    >
                        {createdBy.username}
                    </Link>
                ) : (
                    <span className='text-muted-foreground'>{t('Unknown')}</span>
                );
            },
            enableSorting: false,
            meta: { headerClassName: 'hidden w-32 @xl:table-cell', cellClassName: 'hidden w-32 @xl:table-cell' },
        },
        {
            id: 'last_used_at',
            header: t('Last used'),
            cell: ({ row }) => {
                const lastUsedAt = row.original.attributes.last_used_at;

                return (
                    <span className='whitespace-nowrap text-xs text-muted-foreground'>
                        {lastUsedAt ? dayjs(lastUsedAt).format('ll') : t('Never')}
                    </span>
                );
            },
            enableSorting: false,
            meta: { headerClassName: 'hidden w-28 @2xl:table-cell', cellClassName: 'hidden w-28 @2xl:table-cell' },
        },
        {
            id: 'created_at',
            accessorFn: (apiKey) => apiKey.attributes.created_at,
            header: ({ column }) => <DataTableColumnHeader column={column} title={t('Created')} />,
            cell: ({ row }) => (
                <time
                    className='whitespace-nowrap text-xs text-muted-foreground'
                    dateTime={row.original.attributes.created_at}
                >
                    {dayjs(row.original.attributes.created_at).format('ll')}
                </time>
            ),
            enableSorting: true,
            sortDescFirst: true,
            meta: { headerClassName: 'hidden w-28 @3xl:table-cell', cellClassName: 'hidden w-28 @3xl:table-cell' },
        },
        actionsColumn<AdminApiKey>(1, (apiKey) => <ApiKeyActionsCell apiKey={apiKey} />),
    ] satisfies ColumnDef<AdminApiKey>[];
