import http from '@/api/http';
import { rawDataToServerAllocation } from '@/api/transformers';
import { Allocation } from '@/api/server/getServer';

export default async (uuid: string): Promise<Allocation[]> => {
    const { data } = await http.get(`/api/client/servers/${uuid}/network/allocations`);

    return (data.data || []).map(rawDataToServerAllocation);
};
