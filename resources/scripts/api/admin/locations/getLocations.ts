import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { useContext } from 'react';
import useSWR from 'swr';
import { createContext } from '@/api/admin/admin';

export interface Location {
    id: number;
    short: string;
    long: string;
    createdAt: Date;
    updatedAt: Date;
}

export const rawDataToLocation = ({ attributes }: FractalResponseData): Location => ({
    id: attributes.id,
    short: attributes.short,
    long: attributes.long,
    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),
});

export interface Filters {
    id?: string;
    short?: string;
    long?: string;
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

    return useSWR<PaginatedResult<Location>>([ 'locations', page, filters, sort, sortDirection ], async () => {
        const { data } = await http.get('/api/application/locations', { params: { include: include.join(','), page, ...params } });

        return ({
            items: (data.data || []).map(rawDataToLocation),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
