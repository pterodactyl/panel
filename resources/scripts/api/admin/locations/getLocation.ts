import http from '@/api/http';
import { Location, rawDataToLocation } from '@/api/admin/locations/getLocations';

export default (id: number): Promise<Location> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/locations/${id}`)
            .then(({ data }) => resolve(rawDataToLocation(data)))
            .catch(reject);
    });
};
