import http from '@/api/http';
import { Nest, rawDataToNest } from '@/api/admin/nests/getNests';

export default (id: number): Promise<Nest> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/nests/${id}`)
            .then(({ data }) => resolve(rawDataToNest(data)))
            .catch(reject);
    });
};
