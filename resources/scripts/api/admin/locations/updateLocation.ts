import http from '@/api/http';
import { Location, rawDataToLocation } from '@/api/admin/locations/getLocations';

export default (id: number, short: string, long: string | null, include: string[] = []): Promise<Location> => {
    return new Promise((resolve, reject) => {
        http.patch(
            `/api/application/locations/${id}`,
            {
                short,
                long,
            },
            { params: { include: include.join(',') } },
        )
            .then(({ data }) => resolve(rawDataToLocation(data)))
            .catch(reject);
    });
};
