import type { ColumnDef } from '@tanstack/react-table';
import {
    type ServerAllocation,
    setPrimaryAllocationInput,
    updateAllocationNotesInput,
    useSetPrimaryAllocation,
    useUpdateAllocationNotes,
} from '@/api/server/network/queries';
import { useCurrentServerUuid } from '@/api/server/queries';
import Button from '@/components/elements/Button';
import Can from '@/components/elements/Can';
import Code from '@/components/elements/Code';
import CopyOnClick from '@/components/elements/CopyOnClick';
import InputSpinner from '@/components/elements/InputSpinner';
import DataTableColumnHeader from '@/components/elements/table/DataTableColumnHeader';
import { actionsColumn, RowActions } from '@/components/elements/table/RowActions';
import { TextArea } from '@/components/form/controls';
import DeleteAllocationButton from '@/components/server/network/DeleteAllocationButton';
import { ip } from '@/lib/formatters';
import { useDebouncedCallback } from '@/plugins/useDebouncedCallback';

const AllocationNotesCell = ({ allocation }: { allocation: ServerAllocation }) => {
    const uuid = useCurrentServerUuid()!;
    const updateAllocationNotes = useUpdateAllocationNotes();
    const setAllocationNotes = useDebouncedCallback((notes: string) => {
        updateAllocationNotes.mutate(updateAllocationNotesInput(uuid, allocation.attributes.id, notes));
    }, 750);

    return (
        <InputSpinner visible={updateAllocationNotes.isPending}>
            <TextArea
                aria-label={`Notes for ${allocation.attributes.ip}:${allocation.attributes.port}`}
                className='min-h-9 resize-y bg-input hover:border-input border-transparent'
                placeholder='Notes'
                defaultValue={allocation.attributes.notes || undefined}
                onChange={(event) => setAllocationNotes(event.currentTarget.value)}
                onBlur={setAllocationNotes.flush}
            />
        </InputSpinner>
    );
};

const AllocationActionsCell = ({ allocation }: { allocation: ServerAllocation }) => {
    const uuid = useCurrentServerUuid()!;
    const setPrimaryAllocation = useSetPrimaryAllocation();
    const { attributes } = allocation;

    if (attributes.is_default) {
        return (
            <span className='inline-flex rounded-full bg-primary px-2 py-1 text-xs font-medium text-primary-foreground'>
                Primary
            </span>
        );
    }

    return (
        <RowActions>
            <Can action='allocation.update'>
                <Button.Text
                    size='xsmall'
                    disabled={setPrimaryAllocation.isPending}
                    onClick={() => setPrimaryAllocation.mutate(setPrimaryAllocationInput(uuid, attributes.id))}
                >
                    Make Primary
                </Button.Text>
            </Can>
            <Can action='allocation.delete'>
                <DeleteAllocationButton allocation={allocation} />
            </Can>
        </RowActions>
    );
};

export const allocationColumns: ColumnDef<ServerAllocation>[] = [
    {
        id: 'address',
        accessorFn: (allocation) => allocation.attributes.ip_alias || ip(allocation.attributes.ip),
        header: ({ column }) => <DataTableColumnHeader column={column} title='Address' />,
        cell: ({ row }) => {
            const address = row.original.attributes.ip_alias || ip(row.original.attributes.ip);

            return (
                <CopyOnClick text={address}>
                    <Code dark className='max-w-52 truncate'>
                        {address}
                    </Code>
                </CopyOnClick>
            );
        },
    },
    {
        id: 'port',
        accessorFn: (allocation) => allocation.attributes.port,
        header: ({ column }) => <DataTableColumnHeader column={column} title='Port' />,
        cell: ({ getValue }) => <Code dark>{getValue<number>()}</Code>,
        meta: { headerClassName: 'w-24', cellClassName: 'w-24' },
    },
    {
        id: 'notes',
        accessorFn: (allocation) => allocation.attributes.notes ?? '',
        header: 'Notes',
        cell: ({ row }) => <AllocationNotesCell allocation={row.original} />,
        enableSorting: false,
        meta: { headerClassName: 'hidden md:table-cell', cellClassName: 'hidden md:table-cell min-w-56' },
    },
    actionsColumn<ServerAllocation>(2, (allocation) => <AllocationActionsCell allocation={allocation} />, 'w-52'),
];
