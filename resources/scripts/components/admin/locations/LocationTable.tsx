import { useStore } from '@tanstack/react-form';
import type { ColumnDef } from '@tanstack/react-table';
import { Link } from '@tanstack/react-router';
import { deleteAdminLocationInput, type AdminLocation, useDeleteAdminLocation } from '@/api/admin/locations/queries';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import { actionsColumn, DeleteAction, EditLinkAction, RowActions } from '@/components/elements/table/RowActions';
import { Form, useAppForm } from '@/components/form';
import dayjs from '@/lib/dayjs';
import Translate from '@/components/elements/Translate';
import { useTranslation, type Translation } from '@/sdk/localization';

interface LocationDialogProps {
    location: AdminLocation;
    open: boolean;
    onClose: () => void;
}

function DeleteLocationDialog({ location, open, onClose }: LocationDialogProps) {
    const { t } = useTranslation();
    const { attributes } = location;
    const deleteLocation = useDeleteAdminLocation();
    const form = useAppForm({
        defaultValues: { confirm: '' },
        onSubmit: async () => {
            try {
                await deleteLocation.mutateAsync(deleteAdminLocationInput(attributes.id, attributes.short));
                onClose();
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });
    const isSubmitting = useStore(form.store, (state) => state.isSubmitting);

    return (
        <Dialog
            open={open}
            title={t('Confirm location deletion')}
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <p className='text-sm'>
                <Translate
                    i18nKey='Deleting a location is permanent. This will remove <bold>{{name}}</bold>.'
                    values={{ name: attributes.short }}
                    components={{ bold: <strong /> }}
                />
            </p>
            <Form form={form} className='m-0 mt-6'>
                <form.AppField
                    name='confirm'
                    validators={{
                        onChange: ({ value }) =>
                            value === attributes.short ? undefined : t('The short code must be provided.'),
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type='text'
                            id={`confirm_${attributes.id}`}
                            label={t('Confirm short code')}
                            description={t('Enter the short code to confirm deletion.')}
                        />
                    )}
                </form.AppField>
                <div className='mt-6 flex justify-end gap-2'>
                    <Button.Text type='button' isSecondary onClick={onClose}>
                        {t('Cancel')}
                    </Button.Text>
                    <form.AppForm>
                        <form.SubmitButton color='red'>{t('Delete location')}</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}

const LocationActionsCell = ({ location }: { location: AdminLocation }) => {
    const { t } = useTranslation();

    return (
        <RowActions>
            <EditLinkAction
                aria-label={t('Edit {{name}}', { name: location.attributes.short })}
                to='/panel/locations/$id'
                params={{ id: location.attributes.id }}
            />
            <Dialog.Trigger
                trigger={({ onClick }) => (
                    <DeleteAction
                        aria-label={t('Delete {{name}}', { name: location.attributes.short })}
                        onClick={onClick}
                    />
                )}
            >
                {({ open, onClose }) => <DeleteLocationDialog location={location} open={open} onClose={onClose} />}
            </Dialog.Trigger>
        </RowActions>
    );
};

export const locationColumns = (t: Translation['t']) =>
    [
        {
            id: 'short',
            accessorFn: (location) => location.attributes.short,
            header: ({ column }) => <DataTableColumnHeader column={column} title={t('Location')} />,
            cell: ({ row }) => (
                <div className='w-0 min-w-full'>
                    <Link
                        to='/panel/locations/$id'
                        params={{ id: row.original.attributes.id }}
                        title={row.original.attributes.short}
                        className='block truncate font-medium text-foreground no-underline hover:text-accent'
                    >
                        {row.original.attributes.short}
                    </Link>
                    {row.original.attributes.long ? (
                        <p
                            className='mt-0.5 truncate text-xs text-muted-foreground'
                            title={row.original.attributes.long}
                        >
                            {row.original.attributes.long}
                        </p>
                    ) : null}
                </div>
            ),
            enableSorting: true,
            meta: { headerClassName: 'min-w-48', cellClassName: 'min-w-48' },
        },
        {
            id: 'nodes_count',
            header: t('Nodes'),
            cell: ({ row }) => <span className='tabular-nums'>{row.original.attributes.nodes_count}</span>,
            enableSorting: false,
            meta: { headerClassName: 'hidden w-20 @sm:table-cell', cellClassName: 'hidden w-20 @sm:table-cell' },
        },
        {
            id: 'servers_count',
            header: t('Servers'),
            cell: ({ row }) => <span className='tabular-nums'>{row.original.attributes.servers_count}</span>,
            enableSorting: false,
            meta: { headerClassName: 'hidden w-20 @lg:table-cell', cellClassName: 'hidden w-20 @lg:table-cell' },
        },
        {
            id: 'created_at',
            accessorFn: (location) => location.attributes.created_at,
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
            meta: { headerClassName: 'hidden w-28 @xl:table-cell', cellClassName: 'hidden w-28 @xl:table-cell' },
        },
        actionsColumn<AdminLocation>(2, (location) => <LocationActionsCell location={location} />),
    ] satisfies ColumnDef<AdminLocation>[];
