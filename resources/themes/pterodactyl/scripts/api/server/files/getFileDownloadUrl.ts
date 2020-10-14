import http from '@/api/http';

export default (uuid: string, file: string): Promise<string> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/files/download`, { params: { file } })
            .then(({ data }) => resolve(data.attributes.url))
            .catch(reject);
    });
};
