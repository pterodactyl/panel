import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { rawDataToUser } from '@/api/transformers';
import { createContext, useContext } from 'react';
import useSWR from 'swr';

export interface User {
    id: number;
    externalId: string;
    uuid: string;
    username: string;
    email: string;
    firstName: string;
    lastName: string;
    language: string;
    rootAdmin: boolean;
    tfa: boolean;
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

    return useSWR<PaginatedResult<User>>([ 'users', page ], async () => {
        const { data } = await http.get('/api/application/users', { params: { page } });

        return ({
            items: (data.data || []).map(rawDataToUser),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
