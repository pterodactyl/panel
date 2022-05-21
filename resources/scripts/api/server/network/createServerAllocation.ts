import http from '@/api/http';
import { Allocation } from '@/api/server/getServer';
import { rawDataToServerAllocation } from '@/api/transformers';

export default async (uuid: string): Promise<Allocation> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/network/allocations`);

    return rawDataToServerAllocation(data);
};
