import http from '@/api/http';
import { User, rawDataToUser } from '@/api/admin/users/getUsers';

export default (id: number, include: string[] = []): Promise<User> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/users/${id}`, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToUser(data)))
            .catch(reject);
    });
};
