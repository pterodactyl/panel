import http from '@/api/http';

export default (uuid: string, resource: string, amount: number): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/edit`, {
            resource,
            amount,
        })
            .then(() => resolve())
            .catch(reject);
    });
};
