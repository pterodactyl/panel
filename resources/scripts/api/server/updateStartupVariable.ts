import http from '@/api/http';
import { ServerEggVariable } from '@/api/server/types';
import { rawDataToServerEggVariable } from '@/api/transformers';

export default async (uuid: string, key: string, value: string): Promise<ServerEggVariable> => {
    const { data } = await http.put(`/api/client/servers/${uuid}/startup/variable`, { key, value });

    return rawDataToServerEggVariable(data);
};
