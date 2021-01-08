import http from '@/api/http';
import { Nest, rawDataToNest } from '@/api/admin/nests/getNests';

export default (id: number, name: string, description?: string): Promise<Nest> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/nests/${id}`, {
            name, description,
        })
            .then(({ data }) => resolve(rawDataToNest(data)))
            .catch(reject);
    });
};
