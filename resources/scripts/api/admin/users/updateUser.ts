import http from '@/api/http';
import { User, rawDataToUser } from '@/api/admin/users/getUsers';

export interface Values {
    username: string;
    email: string;
    password: string;
    adminRoleId: number | null;
    rootAdmin: boolean;
}

export default (id: number, values: Partial<Values>, include: string[] = []): Promise<User> => {
    const data = {};
    Object.keys(values).forEach(k => {
        // Don't set password if it is empty.
        if (k === 'password' && values[k] === '') {
            return;
        }
        // @ts-ignore
        data[k.replace(/[A-Z]/g, l => `_${l.toLowerCase()}`)] = values[k];
    });
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/users/${id}`, data, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToUser(data)))
            .catch(reject);
    });
};
