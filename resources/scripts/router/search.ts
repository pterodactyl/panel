import { useCallback } from 'react';
import { useNavigate, useSearch } from '@tanstack/react-router';
import type { AdminNodeListSort } from '@/api/admin/nodes/queries';
import type { AdminServerListSort } from '@/api/admin/servers/queries';
import type { UserListSort } from '@/api/admin/users/queries';
import type { LocationListSort } from '@/api/admin/locations/queries';
import type { AdminDatabaseHostListSort } from '@/api/admin/database-hosts/queries';
import type { AdminMountListSort } from '@/api/admin/mounts/queries';
import type { ApiKeyListSort } from '@/api/admin/api-keys/queries';
import type { AdminTagListSort } from '@/api/admin/tags/queries';
import { parsePageSearch, searchText, type PageSearch, type RawSearch } from '@/router/pageSearch';

export {
    getPageSearch,
    parseActivitySearch,
    parseDashboardSearch,
    parseEmailSearch,
    parsePageSearch,
    useActivitySearch,
    useDashboardSearch,
    usePageSearch,
    type ActivitySearch,
    type DashboardSearch,
    type PageSearch,
    type RawSearch,
} from '@/router/pageSearch';

export type AdminListSearch = PageSearch & {
    filter?: string;
};

export type ServerListSearch = AdminListSearch & {
    sort?: AdminServerListSort;
};

export type NodeListSearch = AdminListSearch & {
    sort?: AdminNodeListSort;
};

export type UserListSearch = AdminListSearch & { sort?: UserListSort };
export type LocationListSearch = AdminListSearch & { sort?: LocationListSort };
export type DatabaseHostListSearch = AdminListSearch & { sort?: AdminDatabaseHostListSort };
export type MountListSearch = AdminListSearch & { sort?: AdminMountListSort };
export type ApiKeyListSearch = AdminListSearch & { sort?: ApiKeyListSort };
export type TagListSearch = AdminListSearch & { sort?: AdminTagListSort };

type AdminListSort =
    | AdminServerListSort
    | AdminNodeListSort
    | UserListSort
    | LocationListSort
    | AdminDatabaseHostListSort
    | AdminMountListSort
    | AdminTagListSort
    | ApiKeyListSort;

export const parseAdminListSearch = (search: RawSearch): AdminListSearch => {
    const result: AdminListSearch = { ...parsePageSearch(search) };
    const filter = searchText(search.filter);

    if (filter) {
        result.filter = filter;
    }

    return result;
};

const parseAllowedSort = <TSort extends string>(value: RawSearch['sort'], allowed: readonly TSort[]) =>
    allowed.find((sort) => sort === value);

const parseSortableAdminListSearch = <TSort extends string>(
    search: RawSearch,
    allowed: readonly TSort[]
): AdminListSearch & { sort?: TSort } => {
    const result: AdminListSearch & { sort?: TSort } = { ...parseAdminListSearch(search) };
    const sort = parseAllowedSort(search.sort, allowed);

    if (sort) {
        result.sort = sort;
    }

    return result;
};

export const parseServerListSearch = (search: RawSearch): ServerListSearch =>
    parseSortableAdminListSearch<AdminServerListSort>(search, ['name', '-name', 'created_at', '-created_at']);

export const parseNodeListSearch = (search: RawSearch): NodeListSearch =>
    parseSortableAdminListSearch<AdminNodeListSort>(search, [
        'name',
        '-name',
        'memory',
        '-memory',
        'disk',
        '-disk',
        'created_at',
        '-created_at',
    ]);

export const parseUserListSearch = (search: RawSearch): UserListSearch =>
    parseSortableAdminListSearch(search, ['email', '-email', 'username', '-username', 'created_at', '-created_at']);

export const parseLocationListSearch = (search: RawSearch): LocationListSearch =>
    parseSortableAdminListSearch(search, ['short', '-short', 'created_at', '-created_at']);

export const parseDatabaseHostListSearch = (search: RawSearch): DatabaseHostListSearch =>
    parseSortableAdminListSearch(search, ['name', '-name', 'created_at', '-created_at']);

export const parseMountListSearch = (search: RawSearch): MountListSearch =>
    parseSortableAdminListSearch(search, ['name', '-name']);

export const parseApiKeyListSearch = (search: RawSearch): ApiKeyListSearch =>
    parseSortableAdminListSearch(search, ['memo', '-memo', 'created_at', '-created_at']);

export const parseTagListSearch = (search: RawSearch): TagListSearch =>
    parseSortableAdminListSearch(search, ['name', '-name', 'slug', '-slug']);

export const WORKBENCH_WIDTHS = ['phone', 'tablet', 'desktop', 'full'] as const;
export type WorkbenchWidth = (typeof WORKBENCH_WIDTHS)[number];
export type WorkbenchSearch = {
    view?: string;
    /** A UI kit primitive, shown instead of a view. */
    ui?: string;
    /** A fixture of the view, or `*` for all of them side by side. */
    fixture?: string;
    scheme?: 'dark' | 'light';
    width?: WorkbenchWidth;
};

export const parseWorkbenchSearch = (search: RawSearch): WorkbenchSearch => {
    const result: WorkbenchSearch = {};
    const view = searchText(search.view);
    const ui = searchText(search.ui);
    const fixture = searchText(search.fixture);
    const scheme = search.scheme === 'dark' || search.scheme === 'light' ? search.scheme : undefined;
    const width = WORKBENCH_WIDTHS.find((preset) => preset === search.width);

    if (view) {
        result.view = view;
    } else if (ui) {
        result.ui = ui;
    }

    if (fixture) {
        result.fixture = fixture;
    }

    if (scheme) {
        result.scheme = scheme;
    }

    if (width) {
        result.width = width;
    }

    return result;
};

interface SortableAdminListSearchUpdate<TSort extends string> {
    page?: number;
    filter?: string;
    sort?: TSort | null;
}

const useSortableAdminListSearch = <TSort extends AdminListSort>(
    parse: (search: RawSearch) => AdminListSearch & { sort?: TSort }
) => {
    const navigate = useNavigate();
    const { page = 1, filter = '', sort } = parse(useSearch({ strict: false }));

    const navigateToSearch = useCallback(
        (next: SortableAdminListSearchUpdate<TSort>) => {
            const nextFilter = next.filter === undefined ? filter : next.filter.trim();
            const nextPage = next.page ?? page;
            const nextSort = next.sort === undefined ? sort : (next.sort ?? undefined);
            const search: AdminListSearch & { sort?: TSort } = {};

            if (nextPage > 1) {
                search.page = nextPage;
            }

            if (nextFilter.length > 0) {
                search.filter = nextFilter;
            }

            if (nextSort) {
                search.sort = nextSort;
            }

            void navigate({
                to: '.',
                search,
                replace: true,
                viewTransition: false,
            });
        },
        [filter, navigate, page, sort]
    );

    return { page, filter, sort, navigateToSearch };
};

export const useServerListSearch = () => useSortableAdminListSearch(parseServerListSearch);
export const useNodeListSearch = () => useSortableAdminListSearch(parseNodeListSearch);
export const useUserListSearch = () => useSortableAdminListSearch(parseUserListSearch);
export const useLocationListSearch = () => useSortableAdminListSearch(parseLocationListSearch);
export const useDatabaseHostListSearch = () => useSortableAdminListSearch(parseDatabaseHostListSearch);
export const useMountListSearch = () => useSortableAdminListSearch(parseMountListSearch);
export const useTagListSearch = () => useSortableAdminListSearch(parseTagListSearch);
export const useApiKeyListSearch = () => useSortableAdminListSearch(parseApiKeyListSearch);
