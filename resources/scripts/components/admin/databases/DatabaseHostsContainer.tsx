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
    adminDatabaseHostListQueryParams,
    type AdminDatabaseHost,
    type AdminDatabaseHostListSort,
    useAdminDatabaseHosts,
} from '@/api/admin/database-hosts/queries';
import { useDatabaseHostListSearch } from '@/router/search';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminListEmpty from '@/components/admin/AdminListEmpty';
import CreateDatabaseHostButton from '@/components/admin/databases/CreateDatabaseHostButton';
import { databaseHostColumns } from '@/components/admin/databases/DatabaseHostTable';
import Spinner from '@/components/elements/Spinner';
import DataTable from '@/components/elements/table/DataTable';
import DataTablePagination from '@/components/elements/table/DataTablePagination';
import { TextInput } from '@/components/form/controls';
import { ServerError } from '@/components/elements/ScreenBlock';
import Icon from '@/components/elements/Icon';
import { Database, Search } from 'lucide-react';

const emptyHosts: AdminDatabaseHost[] = [];

const sortingFromSearch = (sort?: AdminDatabaseHostListSort): SortingState => {
    if (sort === 'name' || sort === '-name') {
        return [{ id: 'name', desc: sort.startsWith('-') }];
    }

    if (sort === 'created_at' || sort === '-created_at') {
        return [{ id: 'created_at', desc: sort.startsWith('-') }];
    }

    return [];
};

const sortingToSearch = (sorting: SortingState): AdminDatabaseHostListSort | null => {
    const primary = sorting[0];

    if (primary?.id === 'name') {
        return primary.desc ? '-name' : 'name';
    }

    if (primary?.id === 'created_at') {
        return primary.desc ? '-created_at' : 'created_at';
    }

    return null;
};

export default function DatabaseHostsContainer() {
    const { page, filter, sort, navigateToSearch } = useDatabaseHostListSearch();

    const {
        data: hosts,
        error,
        isFetching,
        refetch,
    } = useAdminDatabaseHosts(adminDatabaseHostListQueryParams(page, filter, sort), {
        placeholderData: keepPreviousData,
    });
    const paginationMetadata = hosts?.meta.pagination;
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
    const table = useReactTable({
        data: hosts?.data ?? emptyHosts,
        columns: databaseHostColumns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (host) => String(host.attributes.id),
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
            title='Admin · Database Hosts'
            heading='Database Hosts'
            description='Manage the database hosts available to your servers.'
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
                        aria-label='Search database hosts'
                        className='h-9 border-border bg-card pl-10'
                        placeholder='Search database hosts…'
                        defaultValue={filter}
                    />
                </div>
                <CreateDatabaseHostButton />
            </form>
            {hosts ? (
                <>
                    <DataTable
                        table={table}
                        isFetching={isFetching}
                        emptyState={
                            <AdminListEmpty
                                icon={Database}
                                noun='database hosts'
                                filter={filter}
                                onClearFilter={() => navigateToSearch({ page: 1, filter: '' })}
                                description='Add a MySQL or MariaDB host so servers can create databases.'
                                action={<CreateDatabaseHostButton />}
                            />
                        }
                    />
                    <DataTablePagination
                        table={table}
                        total={hosts.meta.pagination.total}
                        count={hosts.meta.pagination.count}
                        itemLabel='database hosts'
                    />
                </>
            ) : (
                <Spinner size='large' centered />
            )}
        </AdminContentBlock>
    );
}
