import { useCallback, useMemo } from 'react';
import { keepPreviousData } from '@tanstack/react-query';
import {
    getCoreRowModel,
    useReactTable,
    type OnChangeFn,
    type PaginationState,
    type SortingState,
} from '@tanstack/react-table';
import { MapPin, Search } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import {
    adminLocationListQueryParams,
    type AdminLocation,
    type LocationListSort,
    useAdminLocations,
} from '@/api/admin/locations/queries';
import CreateLocationButton from '@/components/admin/locations/CreateLocationButton';
import { locationColumns } from '@/components/admin/locations/LocationTable';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminListEmpty from '@/components/admin/AdminListEmpty';
import Icon from '@/components/elements/Icon';
import { ServerError } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import DataTable from '@/components/elements/table/DataTable';
import DataTablePagination from '@/components/elements/table/DataTablePagination';
import { TextInput } from '@/components/form/controls';
import { useLocationListSearch } from '@/router/search';

const emptyLocations: AdminLocation[] = [];
const sortingFromSearch = (sort?: LocationListSort): SortingState => {
    switch (sort) {
        case 'short':
            return [{ id: 'short', desc: false }];
        case '-short':
            return [{ id: 'short', desc: true }];
        case 'created_at':
            return [{ id: 'created_at', desc: false }];
        case '-created_at':
            return [{ id: 'created_at', desc: true }];
        case undefined:
            return [];
    }
};

const sortingToSearch = (sorting: SortingState): LocationListSort | null => {
    const primary = sorting[0];

    if (!primary || !['short', 'created_at'].includes(primary.id)) {
        return null;
    }

    return `${primary.desc ? '-' : ''}${primary.id}` as LocationListSort;
};

export default function LocationsContainer() {
    const { page, filter, sort, navigateToSearch } = useLocationListSearch();
    const {
        data: locations,
        error,
        isFetching,
        refetch,
    } = useAdminLocations(adminLocationListQueryParams(page, filter, sort), { placeholderData: keepPreviousData });
    const metadata = locations?.meta.pagination;
    const pagination = useMemo<PaginationState>(
        () => ({ pageIndex: page - 1, pageSize: metadata?.per_page ?? 50 }),
        [metadata?.per_page, page]
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
        data: locations?.data ?? emptyLocations,
        columns: locationColumns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (location) => String(location.attributes.id),
        manualPagination: true,
        manualSorting: true,
        rowCount: metadata?.total ?? 0,
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
            title='Admin · Locations'
            heading='Locations'
            description='Organize nodes by the regions and facilities where they run.'
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
                        aria-label='Search locations'
                        className='h-9 border-border bg-card pl-10'
                        placeholder='Search locations…'
                        defaultValue={filter}
                    />
                </div>
                <CreateLocationButton />
            </form>
            {locations ? (
                <>
                    <DataTable
                        table={table}
                        isFetching={isFetching}
                        emptyState={
                            <AdminListEmpty
                                icon={MapPin}
                                noun='locations'
                                filter={filter}
                                onClearFilter={() => navigateToSearch({ page: 1, filter: '' })}
                                description='Create a location to group nodes by region or data center.'
                                action={<CreateLocationButton />}
                            />
                        }
                    />
                    <DataTablePagination
                        table={table}
                        total={locations.meta.pagination.total}
                        count={locations.meta.pagination.count}
                        itemLabel='locations'
                    />
                </>
            ) : (
                <Spinner size='large' centered />
            )}
        </AdminContentBlock>
    );
}
