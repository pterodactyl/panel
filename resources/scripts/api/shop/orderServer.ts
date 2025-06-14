import http from '@/api/http';

export default (gameId: string): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/shop/order', {
            gameId,
        }).then((data) => {
            resolve(data.data || []);
        }).catch(reject);
    });
};
