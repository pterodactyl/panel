import { useCallback, useMemo } from 'react';
import { keepPreviousData } from '@tanstack/react-query';
import {
    getCoreRowModel,
    useReactTable,
    type OnChangeFn,
    type PaginationState,
    type SortingState,
} from '@tanstack/react-table';
import { Search, Tags } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import { adminTagListQueryParams, type AdminTag, type AdminTagListSort, useAdminTags } from '@/api/admin/tags/queries';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminListEmpty from '@/components/admin/AdminListEmpty';
import CreateTagButton from '@/components/admin/tags/CreateTagButton';
import { tagColumns } from '@/components/admin/tags/TagTable';
import Icon from '@/components/elements/Icon';
import { ServerError } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import DataTable from '@/components/elements/table/DataTable';
import DataTablePagination from '@/components/elements/table/DataTablePagination';
import { TextInput } from '@/components/form/controls';
import { useTagListSearch } from '@/router/search';

const emptyTags: AdminTag[] = [];

const sortingFromSearch = (sort?: AdminTagListSort): SortingState => {
    switch (sort) {
        case 'name':
            return [{ id: 'name', desc: false }];
        case '-name':
            return [{ id: 'name', desc: true }];
        case 'slug':
            return [{ id: 'slug', desc: false }];
        case '-slug':
            return [{ id: 'slug', desc: true }];
        default:
            return [];
    }
};

const sortingToSearch = (sorting: SortingState): AdminTagListSort | null => {
    const primary = sorting[0];
    if (!primary || !['name', 'slug'].includes(primary.id)) return null;

    return `${primary.desc ? '-' : ''}${primary.id}` as AdminTagListSort;
};

export default function TagsContainer() {
    const { page, filter, sort, navigateToSearch } = useTagListSearch();
    const {
        data: tags,
        error,
        isFetching,
        refetch,
    } = useAdminTags(adminTagListQueryParams(page, filter, sort), { placeholderData: keepPreviousData });
    const metadata = tags?.meta.pagination;
    const pagination = useMemo<PaginationState>(
        () => ({ pageIndex: page - 1, pageSize: metadata?.per_page ?? 50 }),
        [metadata?.per_page, page]
    );
    const sorting = useMemo(() => sortingFromSearch(sort), [sort]);
    const onPaginationChange = useCallback<OnChangeFn<PaginationState>>(
        (updater) => {
            const next = updater instanceof Function ? updater(pagination) : updater;
            if (next.pageIndex !== pagination.pageIndex) navigateToSearch({ page: next.pageIndex + 1 });
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
        data: tags?.data ?? emptyTags,
        columns: tagColumns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (tag) => String(tag.attributes.id),
        manualPagination: true,
        manualSorting: true,
        rowCount: metadata?.total ?? 0,
        enableMultiSort: false,
        state: { pagination, sorting },
        onPaginationChange,
        onSortingChange,
    });

    if (error) return <ServerError message={httpErrorToHuman(error)} onRetry={() => refetch()} />;

    return (
        <AdminContentBlock
            title={'Admin · Tags'}
            heading={'Tags'}
            description={'Organize eggs and control which nodes can run them.'}
        >
            <form
                className={'mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between'}
                onSubmit={(event) => {
                    event.preventDefault();
                    const value = new FormData(event.currentTarget).get('filter');
                    navigateToSearch({ page: 1, filter: value instanceof File || value === null ? '' : value.trim() });
                }}
            >
                <div className={'relative w-full sm:max-w-lg'}>
                    <Icon
                        icon={Search}
                        aria-hidden={'true'}
                        className={
                            'pointer-events-none absolute left-3.5 top-1/2 z-10 h-4 w-4 -translate-y-1/2 text-muted-foreground'
                        }
                    />
                    <TextInput
                        key={filter}
                        name={'filter'}
                        aria-label={'Search tags'}
                        className={'h-9 border-border bg-card pl-10'}
                        placeholder={'Search tags…'}
                        defaultValue={filter}
                    />
                </div>
                <CreateTagButton />
            </form>
            {!tags ? (
                <Spinner size={'large'} centered />
            ) : (
                <>
                    <DataTable
                        table={table}
                        tableClassName={'table-fixed'}
                        isFetching={isFetching}
                        emptyState={
                            <AdminListEmpty
                                icon={Tags}
                                noun={'tags'}
                                filter={filter}
                                onClearFilter={() => navigateToSearch({ page: 1, filter: '' })}
                                description={'Create a tag to organize eggs and match them with nodes.'}
                                action={<CreateTagButton />}
                            />
                        }
                    />
                    <DataTablePagination
                        table={table}
                        total={tags.meta.pagination.total}
                        count={tags.meta.pagination.count}
                        itemLabel={'tags'}
                    />
                </>
            )}
        </AdminContentBlock>
    );
}
