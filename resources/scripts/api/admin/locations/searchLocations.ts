import http from '@/api/http';
import { Location, rawDataToLocation } from '@/api/admin/locations/getLocations';

interface Filters {
    short?: string;
    long?: string;
}

export default (filters?: Filters): Promise<Location[]> => {
    const params = {};
    if (filters !== undefined) {
        Object.keys(filters).forEach(key => {
            // @ts-expect-error todo
            params['filter[' + key + ']'] = filters[key];
        });
    }

    return new Promise((resolve, reject) => {
        http.get('/api/application/locations', { params })
            .then(response => resolve((response.data.data || []).map(rawDataToLocation)))
            .catch(reject);
    });
};
