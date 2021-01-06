import http from '@/api/http';
import { Mount, rawDataToMount } from '@/api/admin/mounts/getMounts';

export default (id: number): Promise<Mount> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/mounts/${id}`)
            .then(({ data }) => resolve(rawDataToMount(data)))
            .catch(reject);
    });
};
