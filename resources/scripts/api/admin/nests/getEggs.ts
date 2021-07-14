import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { createContext, useContext } from 'react';
import useSWR from 'swr';
import { Egg, rawDataToEgg } from '@/api/admin/eggs/getEgg';

export interface Filters {
    id?: string;
    name?: string;
}

interface ctx {
    page: number;
    setPage: (value: number | ((s: number) => number)) => void;

    filters: Filters | null;
    setFilters: (filters: Filters | null) => void;

    sort: string | null;
    setSort: (sort: string | null) => void;

    sortDirection: boolean;
    setSortDirection: (direction: boolean) => void;
}

export const Context = createContext<ctx>({
    page: 1,
    setPage: () => 1,

    filters: null,
    setFilters: () => null,

    sort: null,
    setSort: () => null,

    sortDirection: false,
    setSortDirection: () => false,
});

export default (nestId: number, include: string[] = []) => {
    const { page, filters, sort, sortDirection } = useContext(Context);

    const params = {};
    if (filters !== null) {
        Object.keys(filters).forEach(key => {
            // @ts-ignore
            params['filter[' + key + ']'] = filters[key];
        });
    }

    if (sort !== null) {
        // @ts-ignore
        params.sort = (sortDirection ? '-' : '') + sort;
    }

    return useSWR<PaginatedResult<Egg>>([ nestId, 'eggs', page, filters, sort, sortDirection ], async () => {
        const { data } = await http.get(`/api/application/nests/${nestId}/eggs`, { params: { include: include.join(','), page, ...params } });

        return ({
            items: (data.data || []).map(rawDataToEgg),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
