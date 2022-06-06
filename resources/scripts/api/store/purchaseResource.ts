import http from '@/api/http';

export default (resource: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/store/resources', { resource })
            .then(() => resolve())
            .catch(reject);
    });
};
