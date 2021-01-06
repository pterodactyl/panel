import http from '@/api/http';
import { Egg, rawDataToEgg } from '@/api/admin/nests/eggs/getEggs';

export default (id: number): Promise<Egg> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/eggs/${id}`)
            .then(({ data }) => resolve(rawDataToEgg(data)))
            .catch(reject);
    });
};
