import http from '@/api/http';

export default (uuid: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/files/upload`)
            .then(({ data }) => resolve(data.attributes.url))
            .catch(reject);
    });
};
