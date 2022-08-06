import http from '@/api/http';

export default (uuid: string, password: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/delete`, { password })
            .then(() => resolve())
            .catch(reject);
    });
};
