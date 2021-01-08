import http from '@/api/http';
import { Egg, rawDataToEgg } from '@/api/admin/eggs/getEgg';

export default (nestId: number): Promise<Egg[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/nests/${nestId}/eggs`)
            .then(({ data }) => resolve((data.data || []).map(rawDataToEgg)))
            .catch(reject);
    });
};
