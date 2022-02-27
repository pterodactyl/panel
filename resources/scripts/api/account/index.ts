import http, { getPaginationSet, PaginatedResult } from '@/api/http';
import { Transformers, Server } from '@definitions/user';
import { PanelPermissions } from '@/state/permissions';

interface QueryParams {
    query?: string;
    page?: number;
    type?: string;
}

const getServers = ({ query, ...params }: QueryParams): Promise<PaginatedResult<Server>> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client', {
            params: {
                'filter[*]': query,
                ...params,
            },
        })
            .then(({ data }) => resolve({
                items: (data.data || []).map(Transformers.toServer),
                pagination: getPaginationSet(data.meta.pagination),
            }))
            .catch(reject);
    });
};

const getSystemPermissions = (): Promise<PanelPermissions> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/permissions')
            .then(({ data }) => resolve(data.attributes.permissions))
            .catch(reject);
    });
};

const updateAccountEmail = (email: string, password: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put('/api/client/account/email', { email, password })
            .then(() => resolve())
            .catch(reject);
    });
};

interface UpdateAccountPasswordData {
    current: string;
    password: string;
    confirmPassword: string;
}

const updateAccountPassword = ({ current, password, confirmPassword }: UpdateAccountPasswordData): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.put('/api/client/account/password', {
            current_password: current,
            password: password,
            password_confirmation: confirmPassword,
        })
            .then(() => resolve())
            .catch(reject);
    });
};

export { getServers, getSystemPermissions, updateAccountEmail, updateAccountPassword };
