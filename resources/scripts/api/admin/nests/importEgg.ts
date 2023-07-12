import http from '@/api/http';
import { Egg, rawDataToEgg } from '@/api/admin/eggs/getEgg';

export default (id: number, content: any, type = 'application/json', include: string[] = []): Promise<Egg> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/application/nests/${id}/import`, content, {
            headers: {
                'Content-Type': type,
            },
            params: {
                include: include.join(','),
            },
        })
            .then(({ data }) => resolve(rawDataToEgg(data)))
            .catch(reject);
    });
};
