import { rawDataToServerHook, Hook } from '@/api/server/hooks/getServerHooks';
import http from '@/api/http';

type Data = Pick<Hook, 'name' | 'enabled'> & { id?: number };

export default async (uuid: string, hook: Data): Promise<Hook> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/hooks${hook.id ? `/${hook.id}` : ''}`, {
        enabled: hook.enabled,
        name: hook.name,
    });

    return rawDataToServerHook(data.attributes);
};
