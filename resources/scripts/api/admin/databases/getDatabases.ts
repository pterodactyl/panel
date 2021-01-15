import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { createContext, useContext } from 'react';
import useSWR from 'swr';

export interface Database {
    id: number;
    name: string;
    host: string;
    port: number;
    username: string;
    maxDatabases: number;
    createdAt: Date;
    updatedAt: Date;

    getAddress (): string;
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

interface ctx {
    page: number;
    setPage: (value: number | ((s: number) => number)) => void;
}

export const Context = createContext<ctx>({ page: 1, setPage: () => 1 });

export default (include: string[] = []) => {
    const { page } = useContext(Context);

    return useSWR<PaginatedResult<Database>>([ 'databases', page ], async () => {
        const { data } = await http.get('/api/application/databases', { params: { include: include.join(','), page } });

        return ({
            items: (data.data || []).map(rawDataToDatabase),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
