import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { createContext, useContext } from 'react';
import useSWR from 'swr';

export interface Mount {
    id: number;
    uuid: string;
    name: string;
    description: string;
    source: string;
    target: string;
    readOnly: boolean;
    userMountable: boolean;
    createdAt: Date;
    updatedAt: Date;
}

export const rawDataToMount = ({ attributes }: FractalResponseData): Mount => ({
    id: attributes.id,
    uuid: attributes.uuid,
    name: attributes.name,
    description: attributes.description,
    source: attributes.source,
    target: attributes.target,
    readOnly: attributes.read_only,
    userMountable: attributes.user_mountable,
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

    return useSWR<PaginatedResult<Mount>>([ 'mounts', page ], async () => {
        const { data } = await http.get('/api/application/mounts', { params: { page } });

        return ({
            items: (data.data || []).map(rawDataToMount),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
