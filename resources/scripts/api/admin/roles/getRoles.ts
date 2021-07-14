import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { createContext, useContext } from 'react';
import useSWR from 'swr';

export interface Role {
    id: number;
    name: string;
    description?: string;
}

export const rawDataToRole = ({ attributes }: FractalResponseData): Role => ({
    id: attributes.id,
    name: attributes.name,
    description: attributes.description,
});

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

export default (include: string[] = []) => {
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

    return useSWR<PaginatedResult<Role>>([ 'roles', page, filters, sort, sortDirection ], async () => {
        const { data } = await http.get('/api/application/roles', { params: { include: include.join(','), page, ...params } });

        return ({
            items: (data.data || []).map(rawDataToRole),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
