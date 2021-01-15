import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
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
    avatarURL: string;
    roleName: string | null;
    createdAt: Date;
    updatedAt: Date;
}

export const rawDataToUser = ({ attributes }: FractalResponseData): User => ({
    id: attributes.id,
    externalId: attributes.external_id,
    uuid: attributes.uuid,
    username: attributes.username,
    email: attributes.email,
    firstName: attributes.first_name,
    lastName: attributes.last_name,
    language: attributes.language,
    rootAdmin: attributes.root_admin,
    tfa: attributes['2fa'],
    avatarURL: attributes.avatar_url,
    roleName: attributes.role_name,
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

    return useSWR<PaginatedResult<User>>([ 'users', page ], async () => {
        const { data } = await http.get('/api/application/users', { params: { include: include.join(','), page } });

        return ({
            items: (data.data || []).map(rawDataToUser),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
