import http from '@/api/http';
import { Location, rawDataToLocation } from '@/api/admin/locations/getLocations';

export default (id: number, include: string[] = []): Promise<Location> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/locations/${id}`, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToLocation(data)))
            .catch(reject);
    });
};
