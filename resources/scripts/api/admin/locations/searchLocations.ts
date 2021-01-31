import http from '@/api/http';
import { Location, rawDataToLocation } from '@/api/admin/locations/getLocations';

export default (filters?: Record<string, string>): Promise<Location[]> => {
    const params = {};
    if (filters !== undefined) {
        Object.keys(filters).forEach(key => {
            // @ts-ignore
            params['filter[' + key + ']'] = filters[key];
        });
    }

    return new Promise((resolve, reject) => {
        http.get('/api/application/locations', { params: { ...params } })
            .then(response => resolve(
                (response.data.data || []).map(rawDataToLocation)
            ))
            .catch(reject);
    });
};
