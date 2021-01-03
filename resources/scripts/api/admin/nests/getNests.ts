import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { rawDataToNest } from '@/api/transformers';
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
