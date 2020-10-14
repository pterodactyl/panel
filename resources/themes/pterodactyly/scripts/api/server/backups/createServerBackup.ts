import http from '@/api/http';
import { ServerBackup } from '@/api/server/types';
import { rawDataToServerBackup } from '@/api/transformers';

export default (uuid: string, name?: string, ignored?: string): Promise<ServerBackup> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/backups`, {
            name, ignored,
        })
            .then(({ data }) => resolve(rawDataToServerBackup(data)))
            .catch(reject);
    });
};
