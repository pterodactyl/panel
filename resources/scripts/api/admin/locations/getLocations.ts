import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { createContext, useContext } from 'react';
import useSWR from 'swr';

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

interface ctx {
    page: number;
    setPage: (value: number | ((s: number) => number)) => void;
}

export const Context = createContext<ctx>({ page: 1, setPage: () => 1 });

export default (include: string[] = []) => {
    const { page } = useContext(Context);

    return useSWR<PaginatedResult<Location>>([ 'locations', page ], async () => {
        const { data } = await http.get('/api/application/locations', { params: { include: include.join(','), page } });

        return ({
            items: (data.data || []).map(rawDataToLocation),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
