import http from '@/api/http';
import { Transformers, Server } from '@definitions/user';

const getServer = async (uuid: string): Promise<[ Server, string[] ]> => {
    const { data } = await http.get(`/api/client/servers/${uuid}`);

    return [
        Transformers.toServer(data),
        // eslint-disable-next-line camelcase
        data.meta?.is_server_owner ? [ '*' ] : (data.meta?.user_permissions || []),
    ];
};

export { getServer };
