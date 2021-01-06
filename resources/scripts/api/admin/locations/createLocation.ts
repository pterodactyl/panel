import http from '@/api/http';
import { Location } from '@/api/admin/locations/getLocations';

export default (short: string, long?: string): Promise<Location> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/locations', {
            short, long,
        })
            .then(({ data }) => resolve(data.attributes))
            .catch(reject);
    });
};
