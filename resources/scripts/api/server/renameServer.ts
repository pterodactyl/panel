import http from '@/api/http';

export default (uuid: string, name: string, description?: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/settings/rename`, { name, description })
            .then(() => resolve())
            .catch(reject);
    });
};
