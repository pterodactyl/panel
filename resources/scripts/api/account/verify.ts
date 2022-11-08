import http from '@/api/http';

interface Data {
    success: boolean;
    data: string[];
}

export default (): Promise<Data> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/account/verify')
            .then((data) => resolve(data.data))
            .catch(reject);
    });
};
