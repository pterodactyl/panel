import http from '@/api/http';
import { Server, rawDataToServer } from '@/api/admin/servers/getServers';

export default (id: number, server: Partial<Server>, include: string[] = []): Promise<Server> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/servers/${id}`, {
            ...server,
        }, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToServer(data)))
            .catch(reject);
    });
};
