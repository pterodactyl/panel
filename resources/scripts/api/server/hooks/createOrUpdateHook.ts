import { rawDataToServerHook, Hook } from '@/api/server/hooks/getServerHooks';
import http from '@/api/http';

type Data = Pick<Hook, 'name' | 'action' | 'trigger' | 'enabled'> & { id?: number };

export default async (uuid: string, hook: Data): Promise<Hook> => {
    const { data } = await http.post(`/api/client/servers/${uuid}/hooks${hook.id ? `/${hook.id}` : ''}`, {
        enabled: hook.enabled,
        name: hook.name,
        trigger: hook.trigger,
        action: hook.action,
    });
    return rawDataToServerHook(data.attributes);
};
