import http from '@/api/http';

export default (amount: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/store/stripe', { amount })
            .then((data) => {
                resolve(data.data || []);
            })
            .catch(reject);
    });
};
