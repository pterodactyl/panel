import { useCallback, useMemo } from 'react';
import { keepPreviousData } from '@tanstack/react-query';
import {
    getCoreRowModel,
    useReactTable,
    type OnChangeFn,
    type PaginationState,
    type SortingState,
} from '@tanstack/react-table';
import { KeyRound, Search } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import {
    adminApiKeyListQueryParams,
    type AdminApiKey,
    type ApiKeyListSort,
    useAdminApiKeys,
} from '@/api/admin/api-keys/queries';
import CreateApiKeyButton from '@/components/admin/api/CreateApiKeyButton';
import { apiKeyColumns } from '@/components/admin/api/ApiKeyTable';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminListEmpty from '@/components/admin/AdminListEmpty';
import Icon from '@/components/elements/Icon';
import { ServerError } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import DataTable from '@/components/elements/table/DataTable';
import DataTablePagination from '@/components/elements/table/DataTablePagination';
import { TextInput } from '@/components/form/controls';
import { useApiKeyListSearch } from '@/router/search';

const emptyApiKeys: AdminApiKey[] = [];
const sortingFromSearch = (sort?: ApiKeyListSort): SortingState => {
    switch (sort) {
        case 'memo':
            return [{ id: 'memo', desc: false }];
        case '-memo':
            return [{ id: 'memo', desc: true }];
        case 'created_at':
            return [{ id: 'created_at', desc: false }];
        case '-created_at':
            return [{ id: 'created_at', desc: true }];
        default:
            return [];
    }
};
const sortingToSearch = (sorting: SortingState): ApiKeyListSort | null => {
    const primary = sorting[0];
    if (!primary || !['memo', 'created_at'].includes(primary.id)) return null;
    return `${primary.desc ? '-' : ''}${primary.id}` as ApiKeyListSort;
};

export default function ApiKeysContainer() {
    const { page, filter, sort, navigateToSearch } = useApiKeyListSearch();
    const {
        data: apiKeys,
        error,
        isFetching,
        refetch,
    } = useAdminApiKeys(adminApiKeyListQueryParams(page, filter, sort), { placeholderData: keepPreviousData });
    const metadata = apiKeys?.meta.pagination;
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
        data: apiKeys?.data ?? emptyApiKeys,
        columns: apiKeyColumns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (apiKey) => apiKey.attributes.identifier,
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
            title={'Admin · Application API'}
            heading={'Application API'}
            description={'Manage application API keys used to automate panel administration.'}
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
                        aria-label={'Search application API keys'}
                        className={'h-9 border-border bg-card pl-10'}
                        placeholder={'Search API keys…'}
                        defaultValue={filter}
                    />
                </div>
                <CreateApiKeyButton />
            </form>
            {!apiKeys ? (
                <Spinner size={'large'} centered />
            ) : (
                <>
                    <DataTable
                        table={table}
                        isFetching={isFetching}
                        emptyState={
                            <AdminListEmpty
                                icon={KeyRound}
                                noun={'API keys'}
                                filter={filter}
                                onClearFilter={() => navigateToSearch({ page: 1, filter: '' })}
                                description={'Create an application API key to automate panel administration.'}
                            />
                        }
                    />
                    <DataTablePagination
                        table={table}
                        total={apiKeys.meta.pagination.total}
                        count={apiKeys.meta.pagination.count}
                        itemLabel={'API keys'}
                    />
                </>
            )}
        </AdminContentBlock>
    );
}
