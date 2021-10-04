import http from '@/api/http';
import { User, rawDataToUser } from '@/api/admin/users/getUsers';
import { Values } from '@/api/admin/users/updateUser';

export type { Values };

export default (values: Values, include: string[] = []): Promise<User> => {
    const data = {};
    Object.keys(values).forEach(k => {
        // @ts-ignore
        data[k.replace(/[A-Z]/g, l => `_${l.toLowerCase()}`)] = values[k];
    });
    return new Promise((resolve, reject) => {
        http.post('/api/application/users', data, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToUser(data)))
            .catch(reject);
    });
};
