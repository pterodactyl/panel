import http from '@/api/http';

export default (uuid: string, values: any[]): Promise<any> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/properties/save`, {
            values,
        })
            .then((data) => {
                resolve(data.data || []);
            })
            .catch(reject);
    });
};
