import http from '@/api/http';

export default (uuid: string, backup: string): Promise<void> => {
    return new Promise((resolve, reject) => {
        http.delete(`/api/client/servers/${uuid}/backups/${backup}`)
            .then(() => resolve())
            .catch(reject);
    });
};
