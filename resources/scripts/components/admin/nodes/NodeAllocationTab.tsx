import { createContext, use, useCallback, useMemo, useState } from 'react';
import { Link, useNavigate } from '@tanstack/react-router';
import { getCoreRowModel, useReactTable, type ColumnDef } from '@tanstack/react-table';
import { useAppForm, Form } from '@/components/form';
import { Network, Trash2 } from 'lucide-react';
import {
    type AdminAllocation,
    bulkDeleteAdminNodeAllocationsInput,
    type CreateAllocationValues,
    createAdminNodeAllocationsInput,
    deleteAdminNodeAllocationInput,
    deleteAdminNodeIpBlockAllocationsInput,
    updateAdminNodeAllocationAliasInput,
    useAdminNodeAllocations,
    useBulkDeleteAdminNodeAllocations,
    useCreateAdminNodeAllocations,
    useDeleteAdminNodeAllocation,
    useDeleteAdminNodeIpBlockAllocations,
    useUpdateAdminNodeAllocationAlias,
} from '@/api/admin/nodes/queries';
import { useNodeDetail } from '@/components/admin/nodes/useNodeDetail';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Icon from '@/components/elements/Icon';
import { TextInput } from '@/components/form/controls';
import Checkbox from '@/components/ui/Checkbox';
import Button from '@/components/elements/Button';
import Spinner from '@/components/elements/Spinner';
import DataTable from '@/components/elements/table/DataTable';
import DataTablePagination from '@/components/elements/table/DataTablePagination';
import { actionsColumn, DeleteAction, RowActions } from '@/components/elements/table/RowActions';
import { Dialog } from '@/components/elements/dialog';
import { Alert } from '@/components/elements/alert';
import { Empty, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty';
import { emptyCompactClass } from '@/components/ui/styles';
import { cardTitleClass } from '@/components/ui/typography';
import { httpErrorToHuman } from '@/api/http';
import { cn } from '@/lib/cn';
import { getPageSearch, usePageSearch } from '@/router/search';

const countLabel = (count: number, noun: string) => `${count} ${noun}${count === 1 ? '' : 's'}`;

const ghostDangerClass = 'rounded-sm text-muted-foreground transition-colors hover:bg-popover hover:text-destructive';

const unassignedAllocationIds = (items: AdminAllocation[]) => {
    const ids: number[] = [];
    for (const allocation of items) {
        if (!allocation.attributes.assigned) {
            ids.push(allocation.attributes.id);
        }
    }

    return ids;
};

const groupAllocationsByIp = (items: AdminAllocation[]) => {
    const map = new Map<string, AdminAllocation[]>();

    for (const allocation of items) {
        const list = map.get(allocation.attributes.ip) ?? [];
        list.push(allocation);
        map.set(allocation.attributes.ip, list);
    }

    return Array.from(map.entries());
};

const paginationColumns = [
    { id: 'allocation', header: 'Allocation', cell: () => null },
] as ColumnDef<AdminAllocation>[];

interface AllocationTableContextValue {
    nodeId: number;
    selected: ReadonlySet<number>;
    toggle: (id: number) => void;
    toggleGroup: (items: AdminAllocation[]) => void;
    deleteAllocation: (allocation: AdminAllocation) => void;
}

const AllocationTableContext = createContext<AllocationTableContextValue | null>(null);

const useAllocationTable = (): AllocationTableContextValue => {
    const context = use(AllocationTableContext);
    if (!context) {
        throw new Error('An allocation table cell was rendered outside of its allocation tab.');
    }

    return context;
};

const AllocationGroupCheckbox = ({ allocations }: { allocations: AdminAllocation[] }) => {
    const { selected, toggleGroup } = useAllocationTable();
    const unassignedIds = unassignedAllocationIds(allocations);
    const allSelected = unassignedIds.length > 0 && unassignedIds.every((id) => selected.has(id));
    const ip = allocations[0]?.attributes.ip;

    return (
        <Checkbox
            aria-label={ip ? `Select all unassigned allocations on ${ip}` : 'Select all unassigned allocations'}
            checked={allSelected}
            indeterminate={!allSelected && unassignedIds.some((id) => selected.has(id))}
            disabled={unassignedIds.length === 0}
            onChange={() => toggleGroup(allocations)}
        />
    );
};

const AllocationCheckbox = ({ allocation }: { allocation: AdminAllocation }) => {
    const { selected, toggle } = useAllocationTable();

    return (
        <Checkbox
            aria-label={`Select allocation ${allocation.attributes.ip}:${allocation.attributes.port}`}
            checked={selected.has(allocation.attributes.id)}
            disabled={allocation.attributes.assigned}
            onChange={() => toggle(allocation.attributes.id)}
        />
    );
};

const AllocationAliasCell = ({ allocation }: { allocation: AdminAllocation }) => {
    const { nodeId } = useAllocationTable();
    const savedAlias = allocation.attributes.alias ?? '';
    const [draft, setDraft] = useState<string | null>(null);
    const updateAlias = useUpdateAdminNodeAllocationAlias();

    const saveAlias = () => {
        if (draft === null) return;
        if (draft === savedAlias) {
            setDraft(null);
            return;
        }

        const alias = draft;
        updateAlias.mutate(updateAdminNodeAllocationAliasInput(nodeId, allocation.attributes.id, alias), {
            onSuccess: () => setDraft((current) => (current === alias ? null : current)),
        });
    };

    return (
        <TextInput
            type={'text'}
            value={draft ?? savedAlias}
            placeholder={'Add alias'}
            aria-label={`Alias for ${allocation.attributes.ip}:${allocation.attributes.port}`}
            onChange={(e) => setDraft(e.currentTarget.value)}
            onBlur={saveAlias}
            className={
                'h-8 border border-transparent bg-transparent px-2 py-1 placeholder:text-muted-foreground focus:bg-input'
            }
        />
    );
};

const AllocationServerCell = ({ allocation }: { allocation: AdminAllocation }) => {
    const { assigned, server_id: serverId, server_name: serverName } = allocation.attributes;

    if (!assigned) {
        return (
            <span
                className={
                    'inline-flex items-center rounded-sm border border-border bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground'
                }
            >
                Unassigned
            </span>
        );
    }

    if (serverId === null) {
        return <span className={'text-sm text-muted-foreground'}>Assigned</span>;
    }

    return (
        <Link
            to={'/panel/servers/$id'}
            params={{ id: serverId }}
            className={'block truncate text-sm text-foreground transition-colors hover:text-accent'}
        >
            {serverName ?? 'Assigned'}
        </Link>
    );
};

const AllocationDeleteCell = ({ allocation }: { allocation: AdminAllocation }) => {
    const { deleteAllocation } = useAllocationTable();

    if (allocation.attributes.assigned) {
        return null;
    }

    const address = `${allocation.attributes.ip}:${allocation.attributes.port}`;

    return (
        <RowActions>
            <Dialog.ConfirmTrigger
                title={'Delete this allocation?'}
                confirm={'Delete'}
                trigger={({ onClick }) => (
                    <DeleteAction aria-label={`Delete allocation ${address}`} onClick={onClick} />
                )}
                onConfirmed={(_event, close) => {
                    close();
                    deleteAllocation(allocation);
                }}
            >
                This will permanently delete the <strong>{address}</strong> allocation. This action cannot be undone.
            </Dialog.ConfirmTrigger>
        </RowActions>
    );
};

const allocationColumns = [
    {
        id: 'selected',
        header: ({ table }) => <AllocationGroupCheckbox allocations={table.options.data} />,
        cell: ({ row }) => <AllocationCheckbox allocation={row.original} />,
        meta: { headerClassName: 'w-10', cellClassName: 'w-10' },
    },
    {
        id: 'port',
        header: 'Port',
        cell: ({ row }) => (
            <span className={'font-mono text-sm tabular-nums text-foreground'}>{row.original.attributes.port}</span>
        ),
        meta: { headerClassName: 'w-24', cellClassName: 'w-24' },
    },
    {
        id: 'alias',
        header: 'Alias',
        cell: ({ row }) => <AllocationAliasCell allocation={row.original} />,
        meta: { cellClassName: 'py-2 pl-1' },
    },
    {
        id: 'server',
        header: 'Server',
        cell: ({ row }) => <AllocationServerCell allocation={row.original} />,
        meta: { headerClassName: 'hidden w-44 sm:table-cell', cellClassName: 'hidden sm:table-cell' },
    },
    actionsColumn<AdminAllocation>(1, (allocation) => <AllocationDeleteCell allocation={allocation} />),
] satisfies ColumnDef<AdminAllocation>[];

interface AllocationGroupTableProps {
    ip: string;
    allocations: AdminAllocation[];
    isFetching: boolean;
    onDeleteBlock: (ip: string) => void;
}

const AllocationGroupTable = ({ ip, allocations, isFetching, onDeleteBlock }: AllocationGroupTableProps) => {
    const table = useReactTable({
        data: allocations,
        columns: allocationColumns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (allocation) => String(allocation.attributes.id),
    });
    const unassignedCount = unassignedAllocationIds(allocations).length;

    return (
        <section className={'pt-4'}>
            <div className={'flex items-center justify-between gap-4 px-3 pb-2'}>
                <div className={'flex min-w-0 flex-wrap items-baseline gap-x-3 gap-y-0.5'}>
                    <h3 className={'truncate font-mono text-sm font-medium text-foreground'}>{ip}</h3>
                    <p className={'text-xs text-muted-foreground'}>
                        {countLabel(allocations.length, 'allocation')} · {unassignedCount} unassigned
                    </p>
                </div>
                <Dialog.ConfirmTrigger
                    title={'Delete allocations for IP block?'}
                    confirm={'Delete'}
                    trigger={({ onClick }) => (
                        <button
                            type={'button'}
                            aria-label={`Delete IP block ${ip}`}
                            className={cn(
                                'inline-flex shrink-0 items-center gap-1.5 px-2 py-1 text-xs font-medium',
                                ghostDangerClass
                            )}
                            onClick={onClick}
                        >
                            <Icon icon={Trash2} aria-hidden />
                            Delete IP block
                        </button>
                    )}
                    onConfirmed={(_event, close) => {
                        close();
                        onDeleteBlock(ip);
                    }}
                >
                    This will permanently delete every unassigned allocation for <strong>{ip}</strong>. This action
                    cannot be undone.
                </Dialog.ConfirmTrigger>
            </div>
            <DataTable
                table={table}
                isFetching={isFetching}
                className={'rounded-none border-0 bg-transparent'}
                tableClassName={'table-fixed'}
                emptyState={'No allocations for this address.'}
            />
        </section>
    );
};

export default function NodeAllocationTab() {
    const { node } = useNodeDetail();
    const { attributes } = node;
    const navigate = useNavigate();
    const page = usePageSearch();
    const [selected, setSelected] = useState<number[]>([]);
    const createAllocations = useCreateAdminNodeAllocations();
    const { mutate: deleteAllocation } = useDeleteAdminNodeAllocation();
    const { mutate: bulkDeleteAllocations } = useBulkDeleteAdminNodeAllocations();
    const { mutate: ipBlockDeleteAllocations } = useDeleteAdminNodeIpBlockAllocations();

    const { data: allocations, error, isFetching: loading, refetch } = useAdminNodeAllocations(attributes.id, page);
    const pagination = { pageIndex: Math.max(page - 1, 0), pageSize: allocations?.meta.pagination.per_page ?? 50 };
    const paginationData = useMemo(() => allocations?.data ?? [], [allocations?.data]);
    const paginationTable = useReactTable({
        data: paginationData,
        columns: paginationColumns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (allocation) => String(allocation.attributes.id),
        manualPagination: true,
        rowCount: allocations?.meta.pagination.total ?? 0,
        state: { pagination },
        onPaginationChange: (updater) => {
            const next = updater instanceof Function ? updater(pagination) : updater;
            if (next.pageIndex !== pagination.pageIndex)
                navigate({
                    to: '/panel/nodes/$id/allocation',
                    params: { id: attributes.id },
                    search: getPageSearch(next.pageIndex + 1),
                    replace: true,
                    viewTransition: false,
                });
        },
    });

    const createForm = useAppForm({
        defaultValues: { ip: '', alias: '', ports: '' },
        onSubmit: async ({ value }) => {
            const ports = value.ports
                .split(/[\s,]+/)
                .map((entry) => entry.trim())
                .filter((entry) => entry.length > 0);

            const payload: CreateAllocationValues = { ip: value.ip, alias: value.alias, ports };

            try {
                await createAllocations.mutateAsync(createAdminNodeAllocationsInput(attributes.id, payload));
                createForm.reset();
                navigate({
                    to: '/panel/nodes/$id/allocation',
                    params: { id: attributes.id },
                    search: {},
                    replace: true,
                    viewTransition: false,
                });
            } catch {
                // Error toast is handled by the mutation.
            }
        },
    });

    const grouped = useMemo(() => groupAllocationsByIp(allocations?.data ?? []), [allocations?.data]);

    const toggle = useCallback(
        (id: number) =>
            setSelected((current) => (current.includes(id) ? current.filter((v) => v !== id) : [...current, id])),
        []
    );

    const toggleGroup = useCallback((items: AdminAllocation[]) => {
        const unassignedIds = unassignedAllocationIds(items);
        if (unassignedIds.length === 0) return;

        setSelected((current) => {
            const currentSet = new Set(current);
            const allSelected = unassignedIds.every((id) => currentSet.has(id));
            if (allSelected) {
                const unassignedSet = new Set(unassignedIds);
                return current.filter((id) => !unassignedSet.has(id));
            }
            return [...current, ...unassignedIds.filter((id) => !currentSet.has(id))];
        });
    }, []);

    const onDeleteSingle = useCallback(
        (allocation: AdminAllocation) => {
            deleteAllocation(deleteAdminNodeAllocationInput(attributes.id, allocation.attributes.id), {
                onSuccess: () => {
                    setSelected((current) => current.filter((v) => v !== allocation.attributes.id));
                },
            });
        },
        [attributes.id, deleteAllocation]
    );

    const onBulkDelete = () => {
        bulkDeleteAllocations(bulkDeleteAdminNodeAllocationsInput(attributes.id, selected), {
            onSuccess: () => {
                setSelected([]);
            },
        });
    };

    const onBlockDelete = useCallback(
        (ip: string) => {
            ipBlockDeleteAllocations(deleteAdminNodeIpBlockAllocationsInput(attributes.id, ip), {
                onSuccess: () => {
                    setSelected([]);
                },
            });
        },
        [attributes.id, ipBlockDeleteAllocations]
    );

    const tableContext = useMemo<AllocationTableContextValue>(
        () => ({
            nodeId: attributes.id,
            selected: new Set(selected),
            toggle,
            toggleGroup,
            deleteAllocation: onDeleteSingle,
        }),
        [attributes.id, onDeleteSingle, selected, toggle, toggleGroup]
    );

    const showPagination = !!allocations && (allocations.meta.pagination.total_pages > 1 || page > 1);

    return (
        <div className={'grid grid-cols-1 items-start gap-6 lg:grid-cols-3'}>
            <TitledGreyBox
                className={'lg:col-span-2'}
                contentClassName={'p-0'}
                title={
                    <div className={'flex min-h-5 items-center justify-between gap-3'}>
                        <h2 className={cardTitleClass}>Existing Allocations</h2>
                        {selected.length > 0 ? (
                            <div className={'-my-1 flex items-center gap-3'}>
                                <span className={'text-xs text-muted-foreground'}>{selected.length} selected</span>
                                <Dialog.ConfirmTrigger
                                    title={'Delete selected allocations?'}
                                    confirm={'Delete'}
                                    trigger={({ onClick }) => (
                                        <Button size={'xsmall'} color={'red'} onClick={onClick}>
                                            Delete Selected
                                        </Button>
                                    )}
                                    onConfirmed={(_event, close) => {
                                        close();
                                        onBulkDelete();
                                    }}
                                >
                                    This will permanently delete the {selected.length} selected allocation(s). This
                                    action cannot be undone.
                                </Dialog.ConfirmTrigger>
                            </div>
                        ) : allocations ? (
                            <span className={'text-xs text-muted-foreground'}>
                                {countLabel(allocations.meta.pagination.total, 'allocation')}
                            </span>
                        ) : null}
                    </div>
                }
            >
                {error && !loading ? (
                    <div className={'space-y-4 p-3'}>
                        <Alert type={'danger'}>{httpErrorToHuman(error)}</Alert>
                        <Button.Text type={'button'} onClick={() => refetch()}>
                            Retry
                        </Button.Text>
                    </div>
                ) : !allocations ? (
                    <Spinner size={'large'} centered />
                ) : (
                    <AllocationTableContext.Provider value={tableContext}>
                        {grouped.length === 0 ? (
                            <Empty className={emptyCompactClass}>
                                <EmptyHeader>
                                    <EmptyMedia variant={'icon'}>
                                        <Network />
                                    </EmptyMedia>
                                    <EmptyTitle>No allocations yet</EmptyTitle>
                                    <EmptyDescription>
                                        Assign IP addresses and ports with the form to make them available to servers.
                                    </EmptyDescription>
                                </EmptyHeader>
                            </Empty>
                        ) : (
                            <div className={'divide-y divide-border'}>
                                {grouped.map(([ip, items]) => (
                                    <AllocationGroupTable
                                        key={ip}
                                        ip={ip}
                                        allocations={items}
                                        isFetching={loading}
                                        onDeleteBlock={onBlockDelete}
                                    />
                                ))}
                            </div>
                        )}
                        {showPagination && (
                            <DataTablePagination
                                table={paginationTable}
                                total={allocations.meta.pagination.total}
                                count={allocations.meta.pagination.count}
                                itemLabel={'allocations'}
                                className={'mt-0 border-t border-border px-3 py-2'}
                            />
                        )}
                    </AllocationTableContext.Provider>
                )}
            </TitledGreyBox>
            <Form form={createForm}>
                <TitledGreyBox title={'Assign New Allocations'} contentClassName={'space-y-6 px-3 pt-4'}>
                    <createForm.AppField
                        name={'ip'}
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 1 ? undefined : 'An IP address must be provided.',
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={'allocation_ip'}
                                label={'IP Address'}
                                description={'Enter the IP address to assign ports to.'}
                            />
                        )}
                    </createForm.AppField>
                    <createForm.AppField name={'alias'}>
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={'allocation_alias'}
                                label={'IP Alias'}
                                description={'Optional default alias for these allocations.'}
                            />
                        )}
                    </createForm.AppField>
                    <createForm.AppField
                        name={'ports'}
                        validators={{
                            onChange: ({ value }) =>
                                value.length >= 1 ? undefined : 'At least one port must be provided.',
                        }}
                    >
                        {(field) => (
                            <field.TextField
                                type={'text'}
                                id={'allocation_ports'}
                                label={'Ports'}
                                description={
                                    'Enter individual ports or port ranges (e.g. 25565-25570) separated by ' +
                                    'commas or spaces.'
                                }
                            />
                        )}
                    </createForm.AppField>
                    <div className={'flex justify-end'}>
                        <createForm.AppForm>
                            <createForm.SubmitButton>Create Allocations</createForm.SubmitButton>
                        </createForm.AppForm>
                    </div>
                </TitledGreyBox>
            </Form>
        </div>
    );
}
