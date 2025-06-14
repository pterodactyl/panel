import http from '@/api/http';

export default (type: string, amount: number): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/shop/payment/${type}`, {
            amount,
        }).then((data) => {
            resolve(data.data || []);
        }).catch(reject);
    });
};
