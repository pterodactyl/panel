import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { createContext, useContext } from 'react';
import useSWR from 'swr';

export interface Nest {
    id: number;
    uuid: string;
    author: string;
    name: string;
    description: string | null;
    createdAt: Date;
    updatedAt: Date;
}

export const rawDataToNest = ({ attributes }: FractalResponseData): Nest => ({
    id: attributes.id,
    uuid: attributes.uuid,
    author: attributes.author,
    name: attributes.name,
    description: attributes.description,
    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),
});

interface ctx {
    page: number;
    setPage: (value: number | ((s: number) => number)) => void;
}

export const Context = createContext<ctx>({ page: 1, setPage: () => 1 });

export default () => {
    const { page } = useContext(Context);

    return useSWR<PaginatedResult<Nest>>([ 'nests', page ], async () => {
        const { data } = await http.get('/api/application/nests', { params: { page } });

        return ({
            items: (data.data || []).map(rawDataToNest),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
