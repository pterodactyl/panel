import http, { FractalResponseData } from '@/api/http';

export interface Allocation {
    id: number;
    ip: string;
    alias: string | null;
    port: number;
    notes: string | null;
    assigned: boolean;
}

export const rawDataToAllocation = (data: FractalResponseData): Allocation => ({
    id: data.attributes.id,
    ip: data.attributes.ip,
    alias: data.attributes.ip_alias,
    port: data.attributes.port,
    notes: data.attributes.notes,
    assigned: data.attributes.assigned,
});

export default (uuid: string): Promise<Allocation[]> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/application/nodes/${uuid}/allocations`)
            .then(({ data }) => resolve((data.data || []).map(rawDataToAllocation)))
            .catch(reject);
    });
};
