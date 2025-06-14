import http from '@/api/http';

export default (country: string, address: string, zipcode: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/account/personal', { country, address, zipcode })
            .then(() => resolve())
            .catch(reject);
    });
};
