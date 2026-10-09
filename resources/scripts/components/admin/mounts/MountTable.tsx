import type { ColumnDef } from '@tanstack/react-table';
import { useStore } from '@tanstack/react-form';
import { Link } from '@tanstack/react-router';
import { type AdminMount, deleteAdminMountInput, useDeleteAdminMount } from '@/api/admin/mounts/queries';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import { actionsColumn, DeleteAction, EditLinkAction, RowActions } from '@/components/elements/table/RowActions';
import { Form, useAppForm } from '@/components/form';

interface MountDialogProps {
    mount: AdminMount;
    open: boolean;
    onClose: () => void;
}

function DeleteMountDialog({ mount, open, onClose }: MountDialogProps) {
    const { attributes } = mount;
    const deleteMount = useDeleteAdminMount();
    const deleteForm = useAppForm({
        defaultValues: { confirm: '' },
        onSubmit: async () => {
            try {
                await deleteMount.mutateAsync(deleteAdminMountInput(attributes.id, attributes.name));
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
            title='Confirm mount deletion'
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={onClose}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <p className='text-sm'>
                Deleting a mount is permanent. This will permanently delete <strong>{attributes.name}</strong> and
                remove it from attached eggs and nodes.
            </p>
            <Form form={deleteForm} className='m-0 mt-6'>
                <deleteForm.AppField
                    name='confirm'
                    validators={{
                        onChange: ({ value }) =>
                            value === attributes.name ? undefined : 'The mount name must be provided.',
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type='text'
                            id={`confirm_${attributes.uuid}`}
                            label='Confirm mount name'
                            description='Enter the name of this mount to confirm deletion.'
                        />
                    )}
                </deleteForm.AppField>
                <div className='mt-6 flex justify-end gap-2'>
                    <Button.Text type='button' isSecondary onClick={onClose}>
                        Cancel
                    </Button.Text>
                    <deleteForm.AppForm>
                        <deleteForm.SubmitButton color='red'>Delete mount</deleteForm.SubmitButton>
                    </deleteForm.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}

const MountIdentityCell = ({ mount }: { mount: AdminMount }) => (
    <div className='w-0 min-w-full'>
        <Link
            to='/panel/mounts/$id'
            params={{ id: mount.attributes.id }}
            title={mount.attributes.name}
            className='block truncate font-medium text-foreground no-underline transition-colors hover:text-accent'
        >
            {mount.attributes.name}
        </Link>
        {mount.attributes.description ? (
            <p className='mt-0.5 truncate text-xs text-muted-foreground' title={mount.attributes.description}>
                {mount.attributes.description}
            </p>
        ) : null}
    </div>
);

const MountPathCell = ({ path }: { path: string }) => (
    <code className='block w-0 min-w-full truncate text-xs' title={path}>
        {path}
    </code>
);

const MountActionsCell = ({ mount }: { mount: AdminMount }) => (
    <RowActions>
        <EditLinkAction
            aria-label={`Edit ${mount.attributes.name}`}
            to='/panel/mounts/$id'
            params={{ id: mount.attributes.id }}
        />
        <Dialog.Trigger
            trigger={({ onClick }) => <DeleteAction aria-label={`Delete ${mount.attributes.name}`} onClick={onClick} />}
        >
            {({ open, onClose }) => <DeleteMountDialog mount={mount} open={open} onClose={onClose} />}
        </Dialog.Trigger>
    </RowActions>
);

export const mountColumns = [
    {
        id: 'name',
        accessorFn: (mount) => mount.attributes.name,
        header: ({ column }) => <DataTableColumnHeader column={column} title='Mount' />,
        cell: ({ row }) => <MountIdentityCell mount={row.original} />,
        enableSorting: true,
        meta: { headerClassName: 'min-w-48', cellClassName: 'min-w-48' },
    },
    {
        id: 'source',
        header: 'Source',
        cell: ({ row }) => <MountPathCell path={row.original.attributes.source} />,
        enableSorting: false,
        meta: { headerClassName: 'hidden w-44 @2xl:table-cell', cellClassName: 'hidden w-44 @2xl:table-cell' },
    },
    {
        id: 'target',
        header: 'Target',
        cell: ({ row }) => <MountPathCell path={row.original.attributes.target} />,
        enableSorting: false,
        meta: { headerClassName: 'hidden w-44 @4xl:table-cell', cellClassName: 'hidden w-44 @4xl:table-cell' },
    },
    {
        id: 'eggs_count',
        header: 'Eggs',
        cell: ({ row }) => <span className='tabular-nums'>{row.original.attributes.eggs_count}</span>,
        enableSorting: false,
        meta: { headerClassName: 'hidden w-20 @sm:table-cell', cellClassName: 'hidden w-20 @sm:table-cell' },
    },
    {
        id: 'nodes_count',
        header: 'Nodes',
        cell: ({ row }) => <span className='tabular-nums'>{row.original.attributes.nodes_count}</span>,
        enableSorting: false,
        meta: { headerClassName: 'hidden w-20 @xl:table-cell', cellClassName: 'hidden w-20 @xl:table-cell' },
    },
    actionsColumn<AdminMount>(2, (mount) => <MountActionsCell mount={mount} />),
] satisfies ColumnDef<AdminMount>[];
