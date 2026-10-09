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
import {
    adminServerListQueryParams,
    type AdminServer,
    type AdminServerListSort,
    useAdminServers,
} from '@/api/admin/servers/queries';
import { useServerListSearch } from '@/router/search';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminListEmpty from '@/components/admin/AdminListEmpty';
import { serverColumns } from '@/components/admin/servers/ServerTable';
import Spinner from '@/components/elements/Spinner';
import DataTable from '@/components/elements/table/DataTable';
import DataTablePagination from '@/components/elements/table/DataTablePagination';
import { TextInput } from '@/components/form/controls';
import Icon from '@/components/elements/Icon';
import { Search, Server } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import { ServerError } from '@/components/elements/ScreenBlock';
import CreateServerButton from '@/components/admin/servers/CreateServerButton';

const emptyServers: AdminServer[] = [];

const sortingFromSearch = (sort?: AdminServerListSort): SortingState => {
    switch (sort) {
        case 'name':
            return [{ id: 'name', desc: false }];
        case '-name':
            return [{ id: 'name', desc: true }];
        case 'created_at':
            return [{ id: 'created_at', desc: false }];
        case '-created_at':
            return [{ id: 'created_at', desc: true }];
        case undefined:
            return [];
    }
};

const sortingToSearch = (sorting: SortingState): AdminServerListSort | null => {
    const primary = sorting[0];

    if (!primary) {
        return null;
    }

    if (primary.id === 'name') {
        return primary.desc ? '-name' : 'name';
    }

    if (primary.id === 'created_at') {
        return primary.desc ? '-created_at' : 'created_at';
    }

    return null;
};

export default function ServersContainer() {
    const { page, filter, sort, navigateToSearch } = useServerListSearch();

    const {
        data: servers,
        error,
        isFetching,
        refetch,
    } = useAdminServers(adminServerListQueryParams(page, filter, sort), { placeholderData: keepPreviousData });

    const paginationMetadata = servers?.meta.pagination;
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

    const extensionColumns = useExtensionTableColumns('admin.servers');
    const columns = useMemo(() => [...serverColumns, ...extensionColumns], [extensionColumns]);
    const table = useReactTable({
        data: servers?.data ?? emptyServers,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (server) => String(server.attributes.id),
        manualPagination: true,
        manualSorting: true,
        rowCount: paginationMetadata?.total ?? 0,
        enableMultiSort: false,
        state: { pagination, sorting },
        onPaginationChange,
        onSortingChange,
    });

    if (error) {
        return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;
    }

    return (
        <AdminContentBlock
            title='Admin · Servers'
            heading='Servers'
            description='Create and manage game servers across your nodes.'
        >
            <form
                className='mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between'
                onSubmit={(e) => {
                    e.preventDefault();

                    const form = e.currentTarget;
                    const value = new FormData(form).get('filter');

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
                        aria-label='Search servers'
                        className='h-9 border-border bg-card pl-10'
                        placeholder='Search servers…'
                        defaultValue={filter}
                    />
                </div>
                <CreateServerButton />
            </form>
            {servers ? (
                <>
                    <DataTable
                        table={table}
                        isFetching={isFetching}
                        emptyState={
                            <AdminListEmpty
                                icon={Server}
                                noun='servers'
                                filter={filter}
                                onClearFilter={() => navigateToSearch({ page: 1, filter: '' })}
                                description='Create a server to deploy it on one of your nodes.'
                                action={<CreateServerButton />}
                            />
                        }
                    />
                    <DataTablePagination
                        table={table}
                        total={servers.meta.pagination.total}
                        count={servers.meta.pagination.count}
                        itemLabel='servers'
                    />
                </>
            ) : (
                <Spinner size='large' centered />
            )}
        </AdminContentBlock>
    );
}
