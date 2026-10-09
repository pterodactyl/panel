import { useStore } from '@tanstack/react-form';
import type { ColumnDef, Table, VisibilityState } from '@tanstack/react-table';
import { Link } from '@tanstack/react-router';
import { Eye, EyeOff, Lock, Unlock, Wrench } from 'lucide-react';
import {
    type AdminNode,
    deleteAdminNodeInput,
    useAdminNodeSystemInformation,
    useDeleteAdminNode,
} from '@/api/admin/nodes/queries';
import { NodeStatusBadge } from '@/components/admin/nodes/NodeStatusBadge';
import Button from '@/components/elements/Button';
import { Dialog } from '@/components/elements/dialog';
import Icon from '@/components/elements/Icon';
import SpinnerOverlay from '@/components/elements/SpinnerOverlay';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import { actionsColumn, DeleteAction, EditLinkAction, RowActions } from '@/components/elements/table/RowActions';
import { Form, useAppForm } from '@/components/form';
import { cn } from '@/lib/cn';
import dayjs from '@/lib/dayjs';
import { bytesToString, mbToBytes } from '@/lib/formatters';
import { relationshipAttributes } from '@/api/relationships';

interface NodeDialogProps {
    node: AdminNode;
    open: boolean;
    onClose: () => void;
}

const NodeLiveness = ({ node }: { node: AdminNode }) => {
    const { data, error } = useAdminNodeSystemInformation(node.attributes.id, {
        refetchInterval: 60_000,
        retry: false,
    });

    if (data) {
        return <NodeStatusBadge status={error ? 'offline' : 'online'} title={`Wings ${data.version}`} />;
    }

    if (error) {
        return <NodeStatusBadge status='offline' title={error.message} />;
    }

    return <NodeStatusBadge status='checking' title='Checking node status' />;
};

function DeleteNodeDialog({ node, open, onClose }: NodeDialogProps) {
    const { attributes } = node;
    const deleteNode = useDeleteAdminNode();
    const deleteForm = useAppForm({
        defaultValues: { confirm: '' },
        onSubmit: async () => {
            try {
                await deleteNode.mutateAsync(deleteAdminNodeInput(attributes.id, attributes.name));
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
            title='Confirm node deletion'
            preventExternalClose={isSubmitting}
            hideCloseIcon={isSubmitting}
            onClose={() => {
                onClose();
                deleteForm.reset();
            }}
        >
            <SpinnerOverlay visible={isSubmitting} />
            <p className='text-sm'>
                Deleting a node is permanent. This will remove <strong>{attributes.name}</strong>.
            </p>
            <Form form={deleteForm} className='m-0 mt-6'>
                <deleteForm.AppField
                    name='confirm'
                    validators={{
                        onChange: ({ value }) =>
                            value === attributes.name ? undefined : 'The node name must be provided.',
                    }}
                >
                    {(field) => (
                        <field.TextField
                            type='text'
                            id={`confirm_${attributes.id}`}
                            label='Confirm Node Name'
                            description='Enter the name of this node to confirm deletion.'
                        />
                    )}
                </deleteForm.AppField>
                <div className='mt-6 flex justify-end gap-2'>
                    <Button.Text type='button' isSecondary onClick={onClose}>
                        Cancel
                    </Button.Text>
                    <deleteForm.AppForm>
                        <deleteForm.SubmitButton color='red'>Delete node</deleteForm.SubmitButton>
                    </deleteForm.AppForm>
                </div>
            </Form>
        </Dialog>
    );
}

const NodeIdentityCell = ({ node }: { node: AdminNode }) => {
    const { attributes } = node;

    return (
        <div className='w-0 min-w-full'>
            <Link
                to='/panel/nodes/$id'
                params={{ id: attributes.id }}
                title={attributes.name}
                className='block truncate font-medium text-foreground no-underline transition-colors hover:text-accent'
            >
                {attributes.name}
            </Link>
            <p className='mt-0.5 truncate text-xs text-muted-foreground' title={attributes.fqdn}>
                #{attributes.id} · {attributes.fqdn}
            </p>
        </div>
    );
};

const NodeStatusCell = ({ node }: { node: AdminNode }) => (
    <div className='flex flex-col items-start gap-1'>
        <NodeLiveness node={node} />
        {node.attributes.maintenance_mode ? (
            <span className='inline-flex items-center gap-1 text-xs font-medium text-warning'>
                <Icon icon={Wrench} aria-hidden='true' className='h-3 w-3' />
                Maintenance
            </span>
        ) : null}
    </div>
);

const LocationCell = ({ node }: { node: AdminNode }) => {
    const location = relationshipAttributes(node.attributes.relationships?.location);

    if (!location) {
        return <span className='text-muted-foreground'>#{node.attributes.location_id}</span>;
    }

    return (
        <div className='w-0 min-w-full'>
            <Link
                to='/panel/locations/$id'
                params={{ id: location.id }}
                title={location.short}
                className='block truncate text-foreground no-underline transition-colors hover:text-accent'
            >
                {location.short}
            </Link>
            {location.long ? (
                <p className='mt-0.5 truncate text-xs text-muted-foreground' title={location.long}>
                    {location.long}
                </p>
            ) : null}
        </div>
    );
};

const ResourcesHeader = ({ table }: { table: Table<AdminNode> }) => {
    const memory = table.getColumn('memory');
    const disk = table.getColumn('disk');

    if (!memory || !disk) {
        return <span>Resources</span>;
    }

    return (
        <span className='inline-flex items-center gap-1'>
            <DataTableColumnHeader column={memory} title='Memory' />
            <DataTableColumnHeader column={disk} title='Disk' />
        </span>
    );
};

const ResourcesCell = ({ node }: { node: AdminNode }) => (
    <div className='whitespace-nowrap text-xs tabular-nums'>
        <p className='text-muted-foreground'>
            <span className='text-foreground'>{bytesToString(mbToBytes(node.attributes.memory))}</span> memory
        </p>
        <p className='mt-0.5 text-muted-foreground'>
            <span className='text-foreground'>{bytesToString(mbToBytes(node.attributes.disk))}</span> disk
        </p>
    </div>
);

const AccessCell = ({ node }: { node: AdminNode }) => {
    const { attributes } = node;
    const secure = attributes.scheme === 'https';

    return (
        <div className='flex flex-col items-start gap-0.5 whitespace-nowrap text-xs'>
            <span className={cn('inline-flex items-center gap-1', secure ? 'text-success' : 'text-destructive')}>
                <Icon icon={secure ? Lock : Unlock} aria-hidden='true' className='h-3 w-3' />
                {attributes.scheme.toUpperCase()}
            </span>
            <span className='inline-flex items-center gap-1 text-muted-foreground'>
                <Icon icon={attributes.public ? Eye : EyeOff} aria-hidden='true' className='h-3 w-3' />
                {attributes.public ? 'Public' : 'Private'}
            </span>
        </div>
    );
};

const NodeActionsCell = ({ node }: { node: AdminNode }) => {
    const { id, name } = node.attributes;

    return (
        <RowActions>
            <EditLinkAction aria-label={`Edit ${name}`} to='/panel/nodes/$id/settings' params={{ id }} />
            <Dialog.Trigger trigger={({ onClick }) => <DeleteAction aria-label={`Delete ${name}`} onClick={onClick} />}>
                {({ open, onClose }) => <DeleteNodeDialog node={node} open={open} onClose={onClose} />}
            </Dialog.Trigger>
        </RowActions>
    );
};

export const nodeColumns = [
    {
        id: 'name',
        accessorFn: (node) => node.attributes.name,
        header: ({ column }) => <DataTableColumnHeader column={column} title='Node' />,
        cell: ({ row }) => <NodeIdentityCell node={row.original} />,
        enableSorting: true,
        meta: { headerClassName: 'min-w-40', cellClassName: 'min-w-40' },
    },
    {
        id: 'status',
        header: 'Status',
        cell: ({ row }) => <NodeStatusCell node={row.original} />,
        enableSorting: false,
        meta: { headerClassName: 'w-28', cellClassName: 'w-28' },
    },
    {
        id: 'location',
        header: 'Location',
        cell: ({ row }) => <LocationCell node={row.original} />,
        enableSorting: false,
        meta: { headerClassName: 'hidden w-32 @4xl:table-cell', cellClassName: 'hidden w-32 @4xl:table-cell' },
    },
    {
        id: 'servers_count',
        accessorFn: (node) => node.attributes.servers_count,
        header: 'Servers',
        cell: ({ row }) => <span className='tabular-nums'>{row.original.attributes.servers_count}</span>,
        enableSorting: false,
        meta: { headerClassName: 'w-20', cellClassName: 'w-20' },
    },
    {
        id: 'resources',
        header: ({ table }) => <ResourcesHeader table={table} />,
        cell: ({ row }) => <ResourcesCell node={row.original} />,
        enableSorting: false,
        meta: { headerClassName: 'w-40', cellClassName: 'w-40', sortColumnIds: ['memory', 'disk'] },
    },
    {
        id: 'memory',
        accessorFn: (node) => node.attributes.memory,
        header: 'Memory',
        enableSorting: true,
        sortDescFirst: true,
    },
    {
        id: 'disk',
        accessorFn: (node) => node.attributes.disk,
        header: 'Disk',
        enableSorting: true,
        sortDescFirst: true,
    },
    {
        id: 'access',
        header: 'Access',
        cell: ({ row }) => <AccessCell node={row.original} />,
        enableSorting: false,
        meta: { headerClassName: 'hidden w-20 @2xl:table-cell', cellClassName: 'hidden w-20 @2xl:table-cell' },
    },
    {
        id: 'created_at',
        accessorFn: (node) => node.attributes.created_at,
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
    actionsColumn<AdminNode>(2, (node) => <NodeActionsCell node={node} />),
] satisfies ColumnDef<AdminNode>[];

export const nodeColumnVisibility: VisibilityState = { memory: false, disk: false };
