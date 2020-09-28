import { Allocation } from '@/api/server/getServer';
import http from '@/api/http';
import { rawDataToServerAllocation } from '@/api/transformers';

export default (uuid: string): Promise<Allocation> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/network/allocations/new`)
            .then(({ data }) => resolve(rawDataToServerAllocation(data)))
            .catch(reject);
    });
};
