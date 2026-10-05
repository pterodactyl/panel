import type { ColumnDef } from '@tanstack/react-table';
import { deleteAdminTagInput, type AdminTag, useDeleteAdminTag } from '@/api/admin/tags/queries';
import TagBadge from '@/components/admin/tags/TagBadge';
import TagFormDialog from '@/components/admin/tags/TagFormDialog';
import { Dialog } from '@/components/elements/dialog';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import { actionsColumn, DeleteAction, EditAction, RowActions } from '@/components/elements/table/RowActions';

const TagActionsCell = ({ tag }: { tag: AdminTag }) => {
    const deleteTag = useDeleteAdminTag();
    const { eggs_count: eggsCount, is_predefined: isPredefined, name, nodes_count: nodesCount } = tag.attributes;

    if (isPredefined) {
        return <span className={'text-xs text-muted-foreground'}>Built in</span>;
    }

    return (
        <RowActions>
            <Dialog.Trigger trigger={({ onClick }) => <EditAction aria-label={`Edit ${name}`} onClick={onClick} />}>
                {({ open, onClose }) => <TagFormDialog tag={tag} open={open} onClose={onClose} />}
            </Dialog.Trigger>
            <Dialog.ConfirmTrigger
                title={'Delete tag'}
                confirm={'Delete tag'}
                onConfirmed={async (_event, close) => {
                    try {
                        await deleteTag.mutateAsync(deleteAdminTagInput(tag.attributes.id, name));
                        close();
                    } catch {
                        // Error toast is handled by the mutation.
                    }
                }}
                trigger={({ onClick }) => <DeleteAction aria-label={`Delete ${name}`} onClick={onClick} />}
            >
                <p className={'text-sm leading-relaxed text-muted-foreground'}>
                    This permanently deletes <strong className={'text-foreground'}>{name}</strong> and removes it from{' '}
                    {eggsCount} {eggsCount === 1 ? 'egg' : 'eggs'} and {nodesCount}{' '}
                    {nodesCount === 1 ? 'node' : 'nodes'}.
                </p>
            </Dialog.ConfirmTrigger>
        </RowActions>
    );
};

export const tagColumns = [
    {
        id: 'name',
        accessorFn: (tag) => tag.attributes.name,
        header: ({ column }) => <DataTableColumnHeader column={column} title={'Tag'} />,
        cell: ({ row }) => <TagBadge tag={row.original} />,
        enableSorting: true,
        meta: { headerClassName: 'w-auto', cellClassName: 'w-auto' },
    },
    {
        id: 'slug',
        accessorFn: (tag) => tag.attributes.slug,
        header: ({ column }) => <DataTableColumnHeader column={column} title={'Slug'} />,
        cell: ({ row }) => (
            <span
                className={'block w-0 min-w-full truncate font-mono text-xs text-muted-foreground'}
                title={row.original.attributes.slug}
            >
                {row.original.attributes.slug}
            </span>
        ),
        enableSorting: true,
        meta: { headerClassName: 'hidden w-52 @lg:table-cell', cellClassName: 'hidden w-52 @lg:table-cell' },
    },
    {
        id: 'eggs_count',
        header: 'Eggs',
        cell: ({ row }) => <span className={'tabular-nums'}>{row.original.attributes.eggs_count}</span>,
        enableSorting: false,
        meta: { headerClassName: 'hidden w-20 @xl:table-cell', cellClassName: 'hidden w-20 @xl:table-cell' },
    },
    {
        id: 'nodes_count',
        header: 'Nodes',
        cell: ({ row }) => <span className={'tabular-nums'}>{row.original.attributes.nodes_count}</span>,
        enableSorting: false,
        meta: { headerClassName: 'hidden w-20 @2xl:table-cell', cellClassName: 'hidden w-20 @2xl:table-cell' },
    },
    actionsColumn<AdminTag>(2, (tag) => <TagActionsCell tag={tag} />),
] satisfies ColumnDef<AdminTag>[];
