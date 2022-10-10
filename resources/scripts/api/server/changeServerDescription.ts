import http from '@/api/http';

export default (uuid: string, description: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/settings/change-description`, { description })
            .then(() => resolve())
            .catch(reject);
    });
};
