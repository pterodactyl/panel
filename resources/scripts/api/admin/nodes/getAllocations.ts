import http from '@/api/http';
import { rawDataToServerAllocation } from '@/api/transformers';

export interface Allocation {
    id: number;
    ip: string;
    alias: string | null;
    port: number;
    notes: string | null;
    isDefault: boolean;
}

export default (uuid: string): Promise<[ Allocation, string[] ]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/allocations/${uuid}`)
            .then(({ data }) => resolve([
                rawDataToServerAllocation(data),
                // eslint-disable-next-line camelcase
                data.meta?.is_allocation_owner ? [ '*' ] : (data.meta?.user_permissions || []),
            ]))
            .catch(reject);
    });
};
