import http from '@/api/http';

interface Params {
    name: string;
    description: string | null;
    cpu: number;
    memory: number;
    disk: number;
    ports: number;
    backups: number | null;
    databases: number | null;
}

export default (params: Params, egg: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/store/create', {
            ...params, egg,
        })
            .then(() => resolve())
            .catch(reject);
    });
};
