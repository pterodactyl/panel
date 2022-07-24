import http from '@/api/http';

export default (): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/account/discord')
            .then((data) => resolve(data.data))
            .catch(reject);
    });
};
