import { useCallback, useMemo } from 'react';
import { keepPreviousData } from '@tanstack/react-query';
import {
    getCoreRowModel,
    useReactTable,
    type OnChangeFn,
    type PaginationState,
    type SortingState,
} from '@tanstack/react-table';
import { Search, Users } from 'lucide-react';
import { httpErrorToHuman } from '@/api/http';
import { adminUserListQueryParams, type AdminUser, type UserListSort, useAdminUsers } from '@/api/admin/users/queries';
import CreateUserButton from '@/components/admin/users/CreateUserButton';
import { userColumns } from '@/components/admin/users/UserTable';
import AdminContentBlock from '@/components/admin/AdminContentBlock';
import AdminListEmpty, { filterHighlight } from '@/components/admin/AdminListEmpty';
import Translate from '@/components/elements/Translate';
import Icon from '@/components/elements/Icon';
import { ServerError } from '@/components/elements/ScreenBlock';
import Spinner from '@/components/elements/Spinner';
import DataTable from '@/components/elements/table/DataTable';
import DataTablePagination from '@/components/elements/table/DataTablePagination';
import { TextInput } from '@/components/form/controls';
import { useUserListSearch } from '@/router/search';
import { useTranslation } from '@/sdk/localization';

const emptyUsers: AdminUser[] = [];

const sortingFromSearch = (sort?: UserListSort): SortingState => {
    switch (sort) {
        case 'email':
            return [{ id: 'email', desc: false }];
        case '-email':
            return [{ id: 'email', desc: true }];
        case 'username':
            return [{ id: 'username', desc: false }];
        case '-username':
            return [{ id: 'username', desc: true }];
        case 'created_at':
            return [{ id: 'created_at', desc: false }];
        case '-created_at':
            return [{ id: 'created_at', desc: true }];
        case undefined:
            return [];
    }
};

const sortingToSearch = (sorting: SortingState): UserListSort | null => {
    const primary = sorting[0];

    if (!primary || !['email', 'username', 'created_at'].includes(primary.id)) {
        return null;
    }

    return `${primary.desc ? '-' : ''}${primary.id}` as UserListSort;
};

export default function UsersContainer() {
    const { t } = useTranslation();
    const { page, filter, sort, navigateToSearch } = useUserListSearch();
    const {
        data: users,
        error,
        isFetching,
        refetch,
    } = useAdminUsers(adminUserListQueryParams(page, filter, sort), { placeholderData: keepPreviousData });
    const metadata = users?.meta.pagination;
    const pagination = useMemo<PaginationState>(
        () => ({ pageIndex: page - 1, pageSize: metadata?.per_page ?? 50 }),
        [metadata?.per_page, page]
    );
    const sorting = useMemo(() => sortingFromSearch(sort), [sort]);
    const columns = useMemo(() => userColumns(t), [t]);
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
        data: users?.data ?? emptyUsers,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getRowId: (user) => user.attributes.uuid,
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
            title={t('Admin · Users')}
            heading={t('Users')}
            description={t('Manage panel accounts, permissions, and account access.')}
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
                        aria-label={t('Search users')}
                        className='h-9 border-border bg-card pl-10'
                        placeholder={t('Search users…')}
                        defaultValue={filter}
                    />
                </div>
                <CreateUserButton />
            </form>
            {users ? (
                <>
                    <DataTable
                        table={table}
                        isFetching={isFetching}
                        emptyState={
                            <AdminListEmpty
                                icon={Users}
                                empty={t('No users yet')}
                                noMatch={t('No matching users')}
                                noMatchDescription={
                                    <Translate
                                        i18nKey='No users match <filter>{{filter}}</filter>.'
                                        values={{ filter }}
                                        components={filterHighlight}
                                    />
                                }
                                filter={filter}
                                onClearFilter={() => navigateToSearch({ page: 1, filter: '' })}
                                description={t('Create an account for each person who should sign in to the panel.')}
                                action={<CreateUserButton />}
                            />
                        }
                    />
                    <DataTablePagination
                        table={table}
                        total={users.meta.pagination.total}
                        count={users.meta.pagination.count}
                        summary={(range) =>
                            t(
                                'Showing <value>{{start}}</value> to <value>{{end}}</value> of <value>{{count}}</value> users',
                                range
                            )
                        }
                    />
                </>
            ) : (
                <Spinner size='large' centered />
            )}
        </AdminContentBlock>
    );
}
