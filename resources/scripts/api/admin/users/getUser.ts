import http from '@/api/http';
import { User, rawDataToUser } from '@/api/admin/users/getUsers';

export default (id: number): Promise<User> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/users/${id}`)
            .then(({ data }) => resolve(rawDataToUser(data)))
            .catch(reject);
    });
};
