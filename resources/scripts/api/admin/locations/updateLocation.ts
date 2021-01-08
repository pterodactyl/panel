import http from '@/api/http';
import { Location, rawDataToLocation } from '@/api/admin/locations/getLocations';

export default (id: number, short: string, long?: string): Promise<Location> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/locations/${id}`, {
            short, long,
        })
            .then(({ data }) => resolve(rawDataToLocation(data)))
            .catch(reject);
    });
};
