import http from '@/api/http';
import { Egg, rawDataToEgg } from '@/api/admin/eggs/getEgg';

interface Filters {
    name?: string;
}

export default (nestId: number, filters?: Filters): Promise<Egg[]> => {
    const params = {};
    if (filters !== undefined) {
        Object.keys(filters).forEach(key => {
            // @ts-ignore
            params['filter[' + key + ']'] = filters[key];
        });
    }

    return new Promise((resolve, reject) => {
        http.get(`/api/application/nests/${nestId}/eggs`, { params: { ...params } })
            .then(response => resolve(
                (response.data.data || []).map(rawDataToEgg)
            ))
            .catch(reject);
    });
};
