import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { useContext } from 'react';
import useSWR from 'swr';
import { createContext } from '@/api/admin';

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

export const Context = createContext<Filters>();

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
