import http from '@/api/http';

export default (uuid: string, name: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/delete`, { name })
            .then(() => resolve())
            .catch(reject);
    });
};
