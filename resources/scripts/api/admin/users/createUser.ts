import http from '@/api/http';
import { User, rawDataToUser } from '@/api/admin/users/getUsers';

export default (name: string, include: string[] = []): Promise<User> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/users', {
            name,
        }, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToUser(data)))
            .catch(reject);
    });
};
