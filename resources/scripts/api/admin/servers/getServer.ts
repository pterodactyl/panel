import http from '@/api/http';
import { Server, rawDataToServer } from '@/api/admin/servers/getServers';

export default (id: number, include: string[]): Promise<Server> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/servers/${id}`, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToServer(data)))
            .catch(reject);
    });
};
