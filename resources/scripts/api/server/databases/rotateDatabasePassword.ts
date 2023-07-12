import { rawDataToServerDatabase, ServerDatabase } from '@/api/server/databases/getServerDatabases';
import http from '@/api/http';

export default (uuid: string, database: string): Promise<ServerDatabase> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${uuid}/databases/${database}/rotate-password`)
            .then(response => resolve(rawDataToServerDatabase(response.data.attributes)))
            .catch(reject);
    });
};
