import { useStore } from '@tanstack/react-form';
import type { ColumnDef } from '@tanstack/react-table';
import { Link, useNavigate } from '@tanstack/react-router';
import { ExternalLink } from 'lucide-react';
import { type AdminServer, deleteAdminServerInput, useDeleteAdminServer } from '@/api/admin/servers/queries';
import { useAppForm, Form } from '@/components/form';
import ServerEditAction from '@/components/admin/servers/ServerEditAction';
import { ServerStatusBadge, type ServerStatus } from '@/components/admin/servers/ServerStatusBadge';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';
import DropdownMenu from '@/components/elements/dropdown/DropdownMenu';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import { actionsColumn, DeleteAction, RowActions, RowActionsMenu } from '@/components/elements/table/RowActions';
import dayjs from '@/lib/dayjs';
import { relationshipAttributes } from '@/api/relationships';

interface DeleteServerDialogProps {
    server: AdminServer;
    open: boolean;
    onClose: () => void;
}

export const getServerStatus = (suspended: boolean, installed: number): ServerStatus => {
    if (suspended) {
        return 'suspended';
    }

    if (!installed) {
        return 'installing';
    }

    return 'active';
};

function DeleteServerDialog({ server, open, onClose }: DeleteServerDialogProps) {
    const { attributes } = server;
    const deleteServer = useDeleteAdminServer();
    const form = useAppForm({
        defaultValues: { confirm: '' },
        onSubmit: async () => {
            try {
                await deleteServer.mutateAsync(deleteAdminServerInput(attributes.id, attributes.name));
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
            title='Confirm server deletion'
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={() => {
                onClose();
                form.reset();
            }}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <p className='text-sm'>
                Deleting a server is permanent. This will remove <strong>{attributes.name}</strong> and all associated
                data.
            </p>
            <Form form={form} className='m-0 mt-6'>
                <form.AppField
                    name='confirm'
                    validators={{
                        onChange: ({ value }) =>
                            value === attributes.name ? undefined : 'The server name must be provided.',
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type='text'
                            id={`confirm_${attributes.uuid}`}
                            label='Confirm Server Name'
                            description='Enter the name of this server to confirm deletion.'
                        />
                    )}
                </form.AppField>
                <div className='mt-6 flex justify-end gap-2'>
                    <Button.Text type='button' isSecondary onClick={onClose}>
                        Cancel
                    </Button.Text>
                    <form.AppForm>
                        <form.SubmitButton color='red'>Delete server</form.SubmitButton>
                    </form.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}

const ServerIdentityCell = ({ server }: { server: AdminServer }) => {
    const { attributes } = server;

    return (
        <div className='w-0 min-w-full'>
            <Link
                to='/panel/servers/$id'
                params={{ id: attributes.id }}
                title={attributes.name}
                className='block truncate font-medium text-foreground no-underline transition-colors hover:text-accent'
            >
                {attributes.name}
            </Link>
            <p className='mt-0.5 truncate font-mono text-xs text-muted-foreground' title={attributes.uuid}>
                #{attributes.id} · {attributes.identifier}
            </p>
        </div>
    );
};

const OwnerCell = ({ server }: { server: AdminServer }) => {
    const owner = relationshipAttributes(server.attributes.relationships?.user);

    if (!owner) {
        return <span className='text-muted-foreground'>Unavailable</span>;
    }

    const name = [owner.first_name, owner.last_name].filter(Boolean).join(' ') || owner.username;

    return (
        <div className='w-0 min-w-full'>
            <Link
                to='/panel/users/$id'
                params={{ id: owner.id }}
                title={name}
                className='block truncate text-foreground no-underline transition-colors hover:text-accent'
            >
                {name}
            </Link>
            <p className='mt-0.5 truncate text-xs text-muted-foreground' title={owner.email}>
                {owner.email}
            </p>
        </div>
    );
};

const NodeCell = ({ server }: { server: AdminServer }) => {
    const node = relationshipAttributes(server.attributes.relationships?.node);
    const allocation = relationshipAttributes(server.attributes.relationships?.allocation);
    const address = allocation ? `${allocation.alias ?? allocation.ip}:${allocation.port}` : null;

    return (
        <div className='w-0 min-w-full'>
            {node ? (
                <Link
                    to='/panel/nodes/$id'
                    params={{ id: node.id }}
                    title={node.fqdn}
                    className='block truncate text-foreground no-underline transition-colors hover:text-accent'
                >
                    {node.name}
                </Link>
            ) : (
                <span className='block text-muted-foreground'>Unavailable</span>
            )}
            {address ? (
                <p className='mt-0.5 truncate font-mono text-xs text-muted-foreground' title={address}>
                    {address}
                </p>
            ) : null}
        </div>
    );
};

const ServerActionsCell = ({ server }: { server: AdminServer }) => {
    const navigate = useNavigate();
    const { identifier, name } = server.attributes;

    return (
        <RowActions>
            <ServerEditAction server={server} />
            <Dialog.Trigger trigger={({ onClick }) => <DeleteAction aria-label={`Delete ${name}`} onClick={onClick} />}>
                {({ open, onClose }) => <DeleteServerDialog server={server} open={open} onClose={onClose} />}
            </Dialog.Trigger>
            <RowActionsMenu label={`More actions for ${name}`}>
                <DropdownMenu.Item
                    icon={ExternalLink}
                    onClick={() => navigate({ to: '/server/$id', params: { id: identifier } })}
                >
                    Open in client area
                </DropdownMenu.Item>
            </RowActionsMenu>
        </RowActions>
    );
};

export const serverColumns = [
    {
        id: 'name',
        accessorFn: (server) => server.attributes.name,
        header: ({ column }) => <DataTableColumnHeader column={column} title='Server' />,
        cell: ({ row }) => <ServerIdentityCell server={row.original} />,
        enableSorting: true,
        meta: { headerClassName: 'min-w-40', cellClassName: 'min-w-40' },
    },
    {
        id: 'owner',
        header: 'Owner',
        cell: ({ row }) => <OwnerCell server={row.original} />,
        enableSorting: false,
        meta: { headerClassName: 'hidden w-40 @xl:table-cell', cellClassName: 'hidden w-40 @xl:table-cell' },
    },
    {
        id: 'node',
        header: 'Node',
        cell: ({ row }) => <NodeCell server={row.original} />,
        enableSorting: false,
        meta: { headerClassName: 'hidden w-44 @3xl:table-cell', cellClassName: 'hidden w-44 @3xl:table-cell' },
    },
    {
        id: 'status',
        header: 'Status',
        cell: ({ row }) => (
            <ServerStatusBadge
                status={getServerStatus(row.original.attributes.suspended, row.original.attributes.container.installed)}
            />
        ),
        enableSorting: false,
        meta: { headerClassName: 'hidden w-28 @sm:table-cell', cellClassName: 'hidden w-28 @sm:table-cell' },
    },
    {
        id: 'created_at',
        accessorFn: (server) => server.attributes.created_at,
        header: ({ column }) => <DataTableColumnHeader column={column} title='Created' />,
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
        meta: { headerClassName: 'hidden w-28 @4xl:table-cell', cellClassName: 'hidden w-28 @4xl:table-cell' },
    },
    actionsColumn<AdminServer>(3, (server) => <ServerActionsCell server={server} />),
] satisfies ColumnDef<AdminServer>[];
