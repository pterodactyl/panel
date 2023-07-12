import http from '@/api/http';
import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes';

export default (id: number, include: string[] = []): Promise<Node> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/nodes/${id}`, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToNode(data)))
            .catch(reject);
    });
};
