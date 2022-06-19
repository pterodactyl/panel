import http from '@/api/http';

export default (): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/store/earn')
            .then(() => resolve())
            .catch(reject);
    });
};
