import type { ColumnDef } from '@tanstack/react-table';
import { useStore } from '@tanstack/react-form';
import { Link } from '@tanstack/react-router';
import {
    type AdminDatabaseHost,
    deleteAdminDatabaseHostInput,
    useDeleteAdminDatabaseHost,
} from '@/api/admin/database-hosts/queries';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import { actionsColumn, DeleteAction, EditLinkAction, RowActions } from '@/components/elements/table/RowActions';
import { Form, useAppForm } from '@/components/form';
import dayjs from '@/lib/dayjs';
import { relationshipAttributes } from '@/api/relationships';
import Translate from '@/components/elements/Translate';
import { useTranslation, type Translation } from '@/sdk/localization';

interface DatabaseHostDialogProps {
    host: AdminDatabaseHost;
    open: boolean;
    onClose: () => void;
}

function DeleteDatabaseHostDialog({ host, open, onClose }: DatabaseHostDialogProps) {
    const { t } = useTranslation();
    const deleteDatabaseHost = useDeleteAdminDatabaseHost();
    const deleteForm = useAppForm({
        defaultValues: { confirm: '' },
        onSubmit: async () => {
            try {
                await deleteDatabaseHost.mutateAsync(
                    deleteAdminDatabaseHostInput(host.attributes.id, host.attributes.name)
                );
                onClose();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });
    const isSubmitting = useStore(deleteForm.store, (state) => state.isSubmitting);

    return (
        <Dialog
            open={open}
            title={t('Confirm database host deletion')}
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <p className='text-sm'>
                <Translate
                    i18nKey='Deleting a database host is permanent. This will permanently delete <bold>{{name}}</bold>.'
                    values={{ name: host.attributes.name }}
                    components={{ bold: <strong /> }}
                />
            </p>
            <Form form={deleteForm} className='m-0 mt-6'>
                <deleteForm.AppField
                    name='confirm'
                    validators={{
                        onChange: ({ value }) =>
                            value === host.attributes.name ? undefined : t('The host name must be provided.'),
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type='text'
                            id={`confirm_${host.attributes.id}`}
                            label={t('Confirm host name')}
                            description={t('Enter the name of this host to confirm deletion.')}
                        />
                    )}
                </deleteForm.AppField>
                <div className='mt-6 flex justify-end gap-2'>
                    <Button.Text type='button' isSecondary onClick={onClose}>
                        {t('Cancel')}
                    </Button.Text>
                    <deleteForm.AppForm>
                        <deleteForm.SubmitButton color='red'>{t('Delete host')}</deleteForm.SubmitButton>
                    </deleteForm.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}

const HostIdentityCell = ({ host }: { host: AdminDatabaseHost }) => {
    const { name, username, host: address, port } = host.attributes;
    const connection = `${username}@${address}:${port}`;

    return (
        <div className='w-0 min-w-full'>
            <Link
                to='/panel/databases/$id'
                params={{ id: host.attributes.id }}
                title={name}
                className='block truncate font-medium text-foreground no-underline transition-colors hover:text-accent'
            >
                {name}
            </Link>
            <p className='mt-0.5 truncate font-mono text-xs text-muted-foreground' title={connection}>
                {connection}
            </p>
        </div>
    );
};

const HostNodeCell = ({ host }: { host: AdminDatabaseHost }) => {
    const { t } = useTranslation();
    const node = relationshipAttributes(host.attributes.relationships?.node);

    if (node) {
        return (
            <Link
                to='/panel/nodes/$id'
                params={{ id: node.id }}
                title={node.name}
                className='block w-0 min-w-full truncate text-foreground no-underline hover:text-accent'
            >
                {node.name}
            </Link>
        );
    }

    return (
        <span className='text-muted-foreground'>
            {host.attributes.node_id === null ? t('Global host') : t('Node #{{id}}', { id: host.attributes.node_id })}
        </span>
    );
};

const HostActionsCell = ({ host }: { host: AdminDatabaseHost }) => {
    const { t } = useTranslation();

    return (
        <RowActions>
            <EditLinkAction
                aria-label={t('Edit {{name}}', { name: host.attributes.name })}
                to='/panel/databases/$id'
                params={{ id: host.attributes.id }}
            />
            <Dialog.Trigger
                trigger={({ onClick }) => (
                    <DeleteAction aria-label={t('Delete {{name}}', { name: host.attributes.name })} onClick={onClick} />
                )}
            >
                {({ open, onClose }) => <DeleteDatabaseHostDialog host={host} open={open} onClose={onClose} />}
            </Dialog.Trigger>
        </RowActions>
    );
};

export const databaseHostColumns = (t: Translation['t']) =>
    [
        {
            id: 'name',
            accessorFn: (host) => host.attributes.name,
            header: ({ column }) => <DataTableColumnHeader column={column} title={t('Database host')} />,
            cell: ({ row }) => <HostIdentityCell host={row.original} />,
            enableSorting: true,
            meta: { headerClassName: 'min-w-48', cellClassName: 'min-w-48' },
        },
        {
            id: 'node',
            header: t('Node'),
            cell: ({ row }) => <HostNodeCell host={row.original} />,
            enableSorting: false,
            meta: { headerClassName: 'hidden w-36 @lg:table-cell', cellClassName: 'hidden w-36 @lg:table-cell' },
        },
        {
            id: 'databases_count',
            header: t('Databases'),
            cell: ({ row }) => <span className='tabular-nums'>{row.original.attributes.databases_count}</span>,
            enableSorting: false,
            meta: { headerClassName: 'hidden w-28 @xl:table-cell', cellClassName: 'hidden w-28 @xl:table-cell' },
        },
        {
            id: 'created_at',
            accessorFn: (host) => host.attributes.created_at,
            header: ({ column }) => <DataTableColumnHeader column={column} title={t('Created')} />,
            cell: ({ row }) => (
                <time
                    dateTime={row.original.attributes.created_at}
                    className='whitespace-nowrap text-xs text-muted-foreground'
                >
                    {dayjs(row.original.attributes.created_at).format('MMM D, YYYY')}
                </time>
            ),
            enableSorting: true,
            sortDescFirst: true,
            meta: { headerClassName: 'hidden w-28 @2xl:table-cell', cellClassName: 'hidden w-28 @2xl:table-cell' },
        },
        actionsColumn<AdminDatabaseHost>(2, (host) => <HostActionsCell host={host} />),
    ] satisfies ColumnDef<AdminDatabaseHost>[];
