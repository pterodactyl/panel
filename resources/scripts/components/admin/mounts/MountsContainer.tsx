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
    adminMountListQueryParams,
    type AdminMount,
    type AdminMountListSort,
    useAdminMounts,
} from '@/api/admin/mounts/queries';
import { useMountListSearch } from '@/router/search';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminListEmpty from '@/components/admin/AdminListEmpty';
import CreateMountButton from '@/components/admin/mounts/CreateMountButton';
import { mountColumns } from '@/components/admin/mounts/MountTable';
import Spinner from '@/components/elements/Spinner';
import DataTable from '@/components/elements/table/DataTable';
import DataTablePagination from '@/components/elements/table/DataTablePagination';
import { TextInput } from '@/components/form/controls';
import { ServerError } from '@/components/elements/ScreenBlock';
import Icon from '@/components/elements/Icon';
import { FolderInput, Search } from 'lucide-react';

const emptyMounts: AdminMount[] = [];
const sortingFromSearch = (sort?: AdminMountListSort): SortingState => {
    if (sort === 'name' || sort === '-name') {
        return [{ id: 'name', desc: sort.startsWith('-') }];
    }

    return [];
};

const sortingToSearch = (sorting: SortingState): AdminMountListSort | null => {
    const primary = sorting[0];

    if (primary?.id === 'name') {
        return primary.desc ? '-name' : 'name';
    }

    return null;
};

export default function MountsContainer() {
    const { page, filter, sort, navigateToSearch } = useMountListSearch();

    const {
        data: mounts,
        error,
        isFetching,
        refetch,
    } = useAdminMounts(adminMountListQueryParams(page, filter, sort), { placeholderData: keepPreviousData });
    const paginationMetadata = mounts?.meta.pagination;
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
        data: mounts?.data ?? emptyMounts,
        columns: mountColumns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (mount) => String(mount.attributes.id),
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
            title='Admin · Mounts'
            heading='Mounts'
            description='Manage additional directories that can be mounted into server containers.'
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
                        aria-label='Search mounts'
                        className='h-9 border-border bg-card pl-10'
                        placeholder='Search mounts…'
                        defaultValue={filter}
                    />
                </div>
                <CreateMountButton />
            </form>
            {mounts ? (
                <>
                    <DataTable
                        table={table}
                        isFetching={isFetching}
                        emptyState={
                            <AdminListEmpty
                                icon={FolderInput}
                                noun='mounts'
                                filter={filter}
                                onClearFilter={() => navigateToSearch({ page: 1, filter: '' })}
                                description='Create a mount to share a host directory with server containers.'
                                action={<CreateMountButton />}
                            />
                        }
                    />
                    <DataTablePagination
                        table={table}
                        total={mounts.meta.pagination.total}
                        count={mounts.meta.pagination.count}
                        itemLabel='mounts'
                    />
                </>
            ) : (
                <Spinner size='large' centered />
            )}
        </AdminContentBlock>
    );
}
