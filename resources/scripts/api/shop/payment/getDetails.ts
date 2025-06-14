import http from '@/api/http';

export default (provider: string, sessionId: string, payerId: string): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/shop/payment', {
            provider, sessionId, payerId,
        }).then((data) => {
            resolve(data.data.data || []);
        }).catch(reject);
    });
};
