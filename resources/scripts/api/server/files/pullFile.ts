import http from '@/api/http';

export default (uuid: string, directory: string, url: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/files/pull`, { root: directory, url })
            .then(() => resolve())
            .catch(reject);
    });
};
