import http from '@/api/http';
import { Allocation, rawDataToAllocation } from '@/api/admin/nodes/getAllocations';

export interface Values {
    ip: string;
    ports: number[];
    alias?: string;
}

export default (id: string | number, values: Values, include: string[] = []): Promise<Allocation[]> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/application/nodes/${id}/allocations`, values, { params: { include: include.join(',') } })
            .then(({ data }) => resolve((data || []).map(rawDataToAllocation)))
            .catch(reject);
    });
};
