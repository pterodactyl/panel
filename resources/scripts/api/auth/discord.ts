import http from '@/api/http';

export default (): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post('/auth/discord/login')
            .then((data) => resolve(data.data || []))
            .catch(reject);
    });
};
