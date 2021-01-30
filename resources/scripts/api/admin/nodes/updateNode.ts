import http from '@/api/http';
import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes';

export default (id: number, name: string, description: string | null, include: string[] = []): Promise<Node> => {
    return new Promise((resolve, reject) => {
        http.patch(`/api/application/nodes/${id}`, {
            name, description,
        }, { params: { include: include.join(',') } })
            .then(({ data }) => resolve(rawDataToNode(data)))
            .catch(reject);
    });
};
