import { Allocation } from '@/api/server/getServer';
import http from '@/api/http';
import { rawDataToServerAllocation } from '@/api/transformers';

export default async (uuid: string, ip: string, port: number): Promise<Allocation> => {
    const { data } = await http.put(`/api/client/servers/${uuid}/network/primary`, { ip, port });

    return rawDataToServerAllocation(data);
};
