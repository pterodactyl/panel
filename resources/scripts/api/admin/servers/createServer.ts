import http from '@/api/http';
import { Server, rawDataToServer } from '@/api/admin/servers/getServers';

export default (name: string, description: string): Promise<Server> => {
    return new Promise((resolve, reject) => {
        http.post('/api/application/servers', {
            name, description,
        })
            .then(({ data }) => resolve(rawDataToServer(data)))
            .catch(reject);
    });
};
