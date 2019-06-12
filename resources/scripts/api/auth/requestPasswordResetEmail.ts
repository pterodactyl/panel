import http from '@/api/http';

export default (email: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post('/auth/password', { email })
            .then(() => resolve())
            .catch(reject);
    });
};
