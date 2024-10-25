import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { useContext } from 'react';
import useSWR from 'swr';
import { createContext } from '@/api/admin';

export interface Database {
    id: number;
    name: string;
    host: string;
    port: number;
    username: string;
    maxDatabases: number;
    createdAt: Date;
    updatedAt: Date;

    getAddress(): string;
}

export const rawDataToDatabase = ({ attributes }: FractalResponseData): Database => ({
    id: attributes.id,
    name: attributes.name,
    host: attributes.host,
    port: attributes.port,
    username: attributes.username,
    maxDatabases: attributes.max_databases,
    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),

    getAddress: () => `${attributes.host}:${attributes.port}`,
});

export interface Filters {
    id?: string;
    name?: string;
    host?: string;
}

export const Context = createContext<Filters>();

export default (include: string[] = []) => {
    const { page, filters, sort, sortDirection } = useContext(Context);

    const params = {};
    if (filters !== null) {
        Object.keys(filters).forEach(key => {
            // @ts-expect-error todo
            params['filter[' + key + ']'] = filters[key];
        });
    }

    if (sort !== null) {
        // @ts-expect-error todo
        params.sort = (sortDirection ? '-' : '') + sort;
    }

    return useSWR<PaginatedResult<Database>>(['databases', page, filters, sort, sortDirection], async () => {
        const { data } = await http.get('/api/application/databases', {
            params: { include: include.join(','), page, ...params },
        });

        return {
            items: (data.data || []).map(rawDataToDatabase),
            pagination: getPaginationSet(data.meta.pagination),
        };
    });
};
