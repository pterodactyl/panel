import { useExtensionTableColumns } from '@/extensions/tableColumns';
import { useCallback, useMemo } from 'react';
import { keepPreviousData } from '@tanstack/react-query';
import {
    getCoreRowModel,
    useReactTable,
    type OnChangeFn,
    type PaginationState,
    type SortingState,
} from '@tanstack/react-table';
import { httpErrorToHuman } from '@/api/http';
import {
    adminNodeListQueryParams,
    type AdminNode,
    type AdminNodeListSort,
    useAdminNodes,
} from '@/api/admin/nodes/queries';
import CreateNodeButton from '@/components/admin/nodes/CreateNodeButton';
import { nodeColumns, nodeColumnVisibility } from '@/components/admin/nodes/NodeTable';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminListEmpty from '@/components/admin/AdminListEmpty';
import Icon from '@/components/elements/Icon';
import { ServerError } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import DataTable from '@/components/elements/table/DataTable';
import DataTablePagination from '@/components/elements/table/DataTablePagination';
import { TextInput } from '@/components/form/controls';
import { useNodeListSearch } from '@/router/search';
import { HardDrive, Search } from 'lucide-react';

const emptyNodes: AdminNode[] = [];

const sortingFromSearch = (sort?: AdminNodeListSort): SortingState => {
    switch (sort) {
        case 'name':
            return [{ id: 'name', desc: false }];
        case '-name':
            return [{ id: 'name', desc: true }];
        case 'memory':
            return [{ id: 'memory', desc: false }];
        case '-memory':
            return [{ id: 'memory', desc: true }];
        case 'disk':
            return [{ id: 'disk', desc: false }];
        case '-disk':
            return [{ id: 'disk', desc: true }];
        case 'created_at':
            return [{ id: 'created_at', desc: false }];
        case '-created_at':
            return [{ id: 'created_at', desc: true }];
        case undefined:
            return [];
    }
};

const sortingToSearch = (sorting: SortingState): AdminNodeListSort | null => {
    const primary = sorting[0];

    if (!primary) {
        return null;
    }

    if (primary.id === 'name') {
        return primary.desc ? '-name' : 'name';
    }

    if (primary.id === 'memory') {
        return primary.desc ? '-memory' : 'memory';
    }

    if (primary.id === 'disk') {
        return primary.desc ? '-disk' : 'disk';
    }

    if (primary.id === 'created_at') {
        return primary.desc ? '-created_at' : 'created_at';
    }

    return null;
};

export default function NodesContainer() {
    const { page, filter, sort, navigateToSearch } = useNodeListSearch();
    const {
        data: nodes,
        error,
        isFetching,
        refetch,
    } = useAdminNodes(adminNodeListQueryParams(page, filter, sort), { placeholderData: keepPreviousData });

    const paginationMetadata = nodes?.meta.pagination;
    const pagination = useMemo<PaginationState>(
        () => ({ pageIndex: page - 1, pageSize: paginationMetadata?.per_page ?? 50 }),
        [page, paginationMetadata?.per_page]
    );
    const sorting = useMemo(() => sortingFromSearch(sort), [sort]);

    const onPaginationChange = useCallback<OnChangeFn<PaginationState>>(
        (updater) => {
            const next = updater instanceof Function ? updater(pagination) : updater;

            if (next.pageIndex !== pagination.pageIndex) {
                navigateToSearch({ page: next.pageIndex + 1 });
            }
        },
        [navigateToSearch, pagination]
    );

    const onSortingChange = useCallback<OnChangeFn<SortingState>>(
        (updater) => {
            const next = updater instanceof Function ? updater(sorting) : updater;

            navigateToSearch({ page: 1, sort: sortingToSearch(next) });
        },
        [navigateToSearch, sorting]
    );

    const extensionColumns = useExtensionTableColumns('admin.nodes');
    const columns = useMemo(() => [...nodeColumns, ...extensionColumns], [extensionColumns]);
    const table = useReactTable({
        data: nodes?.data ?? emptyNodes,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (node) => String(node.attributes.id),
        manualPagination: true,
        manualSorting: true,
        rowCount: paginationMetadata?.total ?? 0,
        enableMultiSort: false,
        initialState: { columnVisibility: nodeColumnVisibility },
        state: { pagination, sorting },
        onPaginationChange,
        onSortingChange,
    });

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    return (
        <AdminContentBlock
            title='Admin · Nodes'
            heading='Nodes'
            description='Manage Wings nodes, connectivity, allocations, and server capacity.'
        >
            <form
                className='mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between'
                onSubmit={(event) => {
                    event.preventDefault();
                    const value = new FormData(event.currentTarget).get('filter');

                    navigateToSearch({ page: 1, filter: value instanceof File || value === null ? '' : value.trim() });
                }}
            >
                <div className='relative w-full sm:max-w-lg'>
                    <Icon
                        icon={Search}
                        aria-hidden='true'
                        className='pointer-events-none absolute left-3.5 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-muted-foreground'
                    />
                    <TextInput
                        key={filter}
                        name='filter'
                        aria-label='Search nodes'
                        className='h-9 border-border bg-card pl-10'
                        placeholder='Search nodes…'
                        defaultValue={filter}
                    />
                </div>
                <CreateNodeButton />
            </form>
            {nodes ? (
                <>
                    <DataTable
                        table={table}
                        isFetching={isFetching}
                        emptyState={
                            <AdminListEmpty
                                icon={HardDrive}
                                noun='nodes'
                                filter={filter}
                                onClearFilter={() => navigateToSearch({ page: 1, filter: '' })}
                                description='Add a node running Wings to start hosting servers.'
                                action={<CreateNodeButton />}
                            />
                        }
                    />
                    <DataTablePagination
                        table={table}
                        total={nodes.meta.pagination.total}
                        count={nodes.meta.pagination.count}
                        itemLabel='nodes'
                    />
                </>
            ) : (
                <Spinner size='large' centered />
            )}
        </AdminContentBlock>
    );
}
