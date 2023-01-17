import http from '@/api/http';
import { Nest, rawDataToNest } from '@/api/admin/nests/getNests';

export default (id: number, include: string[]): Promise<Nest> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/nests/${id}`, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToNest(data)))
            .catch(reject);
    });
};
