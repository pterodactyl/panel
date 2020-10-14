import http from '@/api/http';

export default (uuid: string, location: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/files/copy`, { location })
            .then(() => resolve())
            .catch(reject);
    });
};
