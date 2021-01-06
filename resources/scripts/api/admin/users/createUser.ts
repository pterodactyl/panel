import http from '@/api/http';
import { User, rawDataToUser } from '@/api/admin/users/getUsers';

export default (name: string): Promise<User> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/users', {
            name,
        })
            .then(({ data }) => resolve(rawDataToUser(data)))
            .catch(reject);
    });
};
