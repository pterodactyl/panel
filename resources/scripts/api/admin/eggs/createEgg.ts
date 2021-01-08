import http from '@/api/http';
import { Egg, rawDataToEgg } from '@/api/admin/eggs/getEgg';

export default (nestId: number, name: string): Promise<Egg> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/eggs', {
            nestId, name,
        })
            .then(({ data }) => resolve(rawDataToEgg(data.attributes)))
            .catch(reject);
    });
};
