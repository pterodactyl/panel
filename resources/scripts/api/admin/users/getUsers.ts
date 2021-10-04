import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { useContext } from 'react';
import useSWR from 'swr';
import { createContext } from '@/api/admin';
import { rawDataToDatabase } from '@/api/admin/databases/getDatabases';
import { Role } from '@/api/admin/roles/getRoles';

export interface User {
    id: number;
    externalId: string;
    uuid: string;
    username: string;
    email: string;
    language: string;
    adminRoleId: number | null;
    rootAdmin: boolean;
    tfa: boolean;
    avatarURL: string;
    roleName: string | null;
    createdAt: Date;
    updatedAt: Date;

    relationships: {
        role: Role | undefined;
    };
}

export const rawDataToUser = ({ attributes }: FractalResponseData): User => ({
    id: attributes.id,
    externalId: attributes.external_id,
    uuid: attributes.uuid,
    username: attributes.username,
    email: attributes.email,
    language: attributes.language,
    adminRoleId: attributes.admin_role_id,
    rootAdmin: attributes.root_admin,
    tfa: attributes['2fa'],
    avatarURL: attributes.avatar_url,
    roleName: attributes.role_name,
    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),

    relationships: {
        role: attributes.relationships?.role !== undefined && attributes.relationships?.role.object !== 'null_resource' ? rawDataToDatabase(attributes.relationships.role as FractalResponseData) : undefined,
    },
});

export interface Filters {
    id?: string;
    uuid?: string;
    username?: string;
    email?: string;
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

    return useSWR<PaginatedResult<User>>([ 'users', page, filters, sort, sortDirection ], async () => {
        const { data } = await http.get('/api/application/users', { params: { include: include.join(','), page, ...params } });

        return ({
            items: (data.data || []).map(rawDataToUser),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
