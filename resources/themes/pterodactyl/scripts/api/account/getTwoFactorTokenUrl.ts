import http from '@/api/http';

export default (): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/account/two-factor')
            .then(({ data }) => resolve(data.data.image_url_data))
            .catch(reject);
    });
};
