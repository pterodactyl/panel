import http from '@/api/http';
import { User, rawDataToUser } from '@/api/admin/users/getUsers';

interface Filters {
    username?: string;
    email?: string;
}

export default (filters?: Filters): Promise<User[]> => {
    const params = {};
    if (filters !== undefined) {
        Object.keys(filters).forEach(key => {
            // @ts-ignore
            params['filter[' + key + ']'] = filters[key];
        });
    }

    return new Promise((resolve, reject) => {
        http.get('/api/application/users', { params: { ...params } })
            .then(response => resolve(
                (response.data.data || []).map(rawDataToUser)
            ))
            .catch(reject);
    });
};
