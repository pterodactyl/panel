import { rawDataToServerBackup, ServerBackup } from '@/api/server/backups/getServerBackups';
import http from '@/api/http';

export default (uuid: string, name?: string, ignore?: string): Promise<ServerBackup> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/backups`, {
            name, ignore,
        })
            .then(({ data }) => resolve(rawDataToServerBackup(data.attributes)))
            .catch(reject);
    });
};
