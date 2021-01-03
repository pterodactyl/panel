import { Nest } from '@/api/admin/nests/getNests';
import useSWR from 'swr';
import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { rawDataToNest } from '@/api/transformers';
import { createContext, useContext } from 'react';

interface ctx {
    page: number;
    setPage: (value: number | ((s: number) => number)) => void;
}

export const Context = createContext<ctx>({ page: 1, setPage: () => 1 });

export default () => {
    const { page } = useContext(Context);

    return useSWR<PaginatedResult<Nest>>([ 'nests', page ], async () => {
        const { data } = await http.get(`/api/application/nests`, { params: { page } });

        return ({
            items: (data.data || []).map(rawDataToNest),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
