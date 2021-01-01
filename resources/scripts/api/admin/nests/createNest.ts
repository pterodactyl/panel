import http from '@/api/http';
import { Nest } from '@/api/admin/nests/getNests';

export default (name: string, description?: string): Promise<Nest> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/nests', {
            name, description,
        })
            .then(({ data }) => resolve(data.attributes))
            .catch(reject);
    });
};
