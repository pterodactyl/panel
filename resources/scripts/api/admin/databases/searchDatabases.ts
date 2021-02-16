import http from '@/api/http';
import { Database, rawDataToDatabase } from '@/api/admin/databases/getDatabases';

interface Filters {
    name?: string;
    host?: string;
}

export default (filters?: Filters): Promise<Database[]> => {
    const params = {};
    if (filters !== undefined) {
        Object.keys(filters).forEach(key => {
            // @ts-ignore
            params['filter[' + key + ']'] = filters[key];
        });
    }

    return new Promise((resolve, reject) => {
        http.get('/api/application/databases', { params: { ...params } })
            .then(response => resolve(
                (response.data.data || []).map(rawDataToDatabase)
            ))
            .catch(reject);
    });
};
