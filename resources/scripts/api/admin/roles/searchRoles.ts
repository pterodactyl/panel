import http from '@/api/http';
import { Role, rawDataToRole } from '@/api/admin/roles/getRoles';

interface Filters {
    name?: string;
}

export default (filters?: Filters): Promise<Role[]> => {
    const params = {};
    if (filters !== undefined) {
        Object.keys(filters).forEach(key => {
            // @ts-ignore
            params['filter[' + key + ']'] = filters[key];
        });
    }

    return new Promise((resolve, reject) => {
        http.get('/api/application/roles', { params })
            .then(response => resolve(
                (response.data.data || []).map(rawDataToRole)
            ))
            .catch(reject);
    });
};
