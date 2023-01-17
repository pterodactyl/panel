import type { AxiosError } from 'axios';
import type { SWRConfiguration, SWRResponse } from 'swr';
import useSWR from 'swr';

import type { FractalPaginatedResponse, PaginatedResult, QueryBuilderParams } from '@/api/http';
import http, { getPaginationSet, withQueryBuilderParams } from '@/api/http';
import type { User } from '@definitions/admin';
import { Transformers } from '@definitions/admin';

export interface UpdateUserValues {
    externalId: string;
    username: string;
    email: string;
    password: string;
    adminRoleId: number | null;
    rootAdmin: boolean;
}

const filters = ['id', 'uuid', 'external_id', 'username', 'email'] as const;
type Filters = typeof filters[number];

const useGetUsers = (
    params?: QueryBuilderParams<Filters>,
    config?: SWRConfiguration,
): SWRResponse<PaginatedResult<User>, AxiosError> => {
    return useSWR<PaginatedResult<User>>(
        ['/api/application/users', JSON.stringify(params)],
        async () => {
            const { data } = await http.get<FractalPaginatedResponse>('/api/application/users', {
                params: withQueryBuilderParams(params),
            });

            return {
                items: (data.data || []).map(Transformers.toUser),
                pagination: getPaginationSet(data.meta.pagination),
            };
        },
        config || { revalidateOnMount: true, revalidateOnFocus: false },
    );
};

const getUser = (id: number, include: string[] = []): Promise<User> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/users/${id}`, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(Transformers.toUser(data)))
            .catch(reject);
    });
};

const searchUserAccounts = async (params: QueryBuilderParams<'username' | 'email'>): Promise<User[]> => {
    const { data } = await http.get('/api/application/users', {
        params: withQueryBuilderParams(params),
    });

    return data.data.map(Transformers.toUser);
};

const createUser = (values: UpdateUserValues, include: string[] = []): Promise<User> => {
    const data = {};
    Object.keys(values).forEach(k => {
        // @ts-expect-error todo
        data[k.replace(/[A-Z]/g, l => `_${l.toLowerCase()}`)] = values[k];
    });

    return new Promise((resolve, reject) => {
        http.post('/api/application/users', data, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(Transformers.toUser(data)))
            .catch(reject);
    });
};

const updateUser = (id: number, values: Partial<UpdateUserValues>, include: string[] = []): Promise<User> => {
    const data = {};
    Object.keys(values).forEach(k => {
        // Don't set password if it is empty.
        if (k === 'password' && values[k] === '') {
            return;
        }
        // @ts-expect-error todo
        data[k.replace(/[A-Z]/g, l => `_${l.toLowerCase()}`)] = values[k];
    });
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/users/${id}`, data, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(Transformers.toUser(data)))
            .catch(reject);
    });
};

const deleteUser = (id: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/application/users/${id}`)
            .then(() => resolve())
            .catch(reject);
    });
};

export { useGetUsers, getUser, searchUserAccounts, createUser, updateUser, deleteUser };
