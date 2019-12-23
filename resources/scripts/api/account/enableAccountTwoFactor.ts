import http from '@/api/http';

export default (code: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/account/two-factor', { code })
            .then(() => resolve())
            .catch(reject);
    });
};
