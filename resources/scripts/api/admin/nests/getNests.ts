import http from '@/api/http';
import { rawDataToNest } from '@/api/transformers';

export interface Nest {
    id: number;
    uuid: string;
    author: string;
    name: string;
    description: string | null;
    createdAt: Date;
    updatedAt: Date;
}

export default (): Promise<Nest[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/application/nests')
            .then(({ data }) => resolve((data.data || []).map(rawDataToNest)))
            .catch(reject);
    });
};
