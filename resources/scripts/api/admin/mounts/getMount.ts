import http from '@/api/http';
import { Mount, rawDataToMount } from '@/api/admin/mounts/getMounts';

export default (id: number, include: string[] = []): Promise<Mount> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/mounts/${id}`, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToMount(data)))
            .catch(reject);
    });
};
