import http, { FractalResponseData, FractalResponseList, getPaginationSet, PaginatedResult } from '@/api/http';
import { createContext, useContext } from 'react';
import useSWR from 'swr';
import { Egg, rawDataToEgg } from '@/api/admin/eggs/getEgg';

export interface Nest {
    id: number;
    uuid: string;
    author: string;
    name: string;
    description?: string;
    createdAt: Date;
    updatedAt: Date;

    relations: {
        eggs: Egg[] | undefined;
    },
}

export const rawDataToNest = ({ attributes }: FractalResponseData): Nest => ({
    id: attributes.id,
    uuid: attributes.uuid,
    author: attributes.author,
    name: attributes.name,
    description: attributes.description,
    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),

    relations: {
        eggs: ((attributes.relationships?.eggs as FractalResponseList | undefined)?.data || []).map(rawDataToEgg),
    },
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

    return useSWR<PaginatedResult<Nest>>([ 'nests', page, filters, sort, sortDirection ], async () => {
        const { data } = await http.get('/api/application/nests', { params: { include: include.join(','), page, ...params } });

        return ({
            items: (data.data || []).map(rawDataToNest),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
