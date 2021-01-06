import http from '@/api/http';
import { Server, rawDataToServer } from '@/api/admin/servers/getServers';

export default (id: number): Promise<Server> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/servers/${id}`)
            .then(({ data }) => resolve(rawDataToServer(data)))
            .catch(reject);
    });
};
