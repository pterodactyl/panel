import http from '@/api/http';

export default (email: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.post('/auth/password', { email })
            .then(response => resolve(response.data.status || ''))
            .catch(reject);
    });
};
