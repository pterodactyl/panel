import http from '@/api/http';
import { Nest, rawDataToNest } from '@/api/admin/nests/getNests';

interface Filters {
    name?: string;
}

export default (filters?: Filters): Promise<Nest[]> => {
    const params = {};
    if (filters !== undefined) {
        Object.keys(filters).forEach(key => {
            // @ts-ignore
            params['filter[' + key + ']'] = filters[key];
        });
    }

    return new Promise((resolve, reject) => {
        http.get('/api/application/nests', { params: { ...params } })
            .then(response => resolve(
                (response.data.data || []).map(rawDataToNest)
            ))
            .catch(reject);
    });
};
