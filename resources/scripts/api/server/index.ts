import http from '@/api/http';
import { Server, ServerEggVariable, Transformers } from '@definitions/user';

interface TokenResponse {
    token: string;
    socket: string;
}

export type ServerPowerState = 'offline' | 'starting' | 'running' | 'stopping';

export interface ServerStats {
    status: ServerPowerState;
    isSuspended: boolean;
    memoryUsageInBytes: number;
    cpuUsagePercent: number;
    diskUsageInBytes: number;
    networkRxInBytes: number;
    networkTxInBytes: number;
    uptime: number;
}

const getServer = async (uuid: string): Promise<[ Server, string[] ]> => {
    const { data } = await http.get(`/api/client/servers/${uuid}`);

    return [
        Transformers.toServer(data),
        // eslint-disable-next-line camelcase
        data.meta?.is_server_owner ? [ '*' ] : (data.meta?.user_permissions || []),
    ];
};

const getWebsocketToken = async (server: string): Promise<TokenResponse> => {
    const { data } = await http.get(`/api/client/servers/${server}/websocket`);

    return {
        token: data.data.token,
        socket: data.data.socket,
    };
};

const renameServer = async (uuid: string, name: string): Promise<void> => {
    await http.post(`/api/client/servers/${uuid}/settings/rename`, { name });
};

const reinstallServer = async (uuid: string): Promise<void> => {
    await http.post(`/api/client/servers/${uuid}/settings/reinstall`);
};

const setSelectedDockerImage = async (uuid: string, image: string): Promise<void> => {
    await http.put(`/api/client/servers/${uuid}/settings/docker-image`, { docker_image: image });
};

const updateStartupVariable = async (uuid: string, key: string, value: string): Promise<[ ServerEggVariable, string ]> => {
    const { data } = await http.put(`/api/client/servers/${uuid}/startup/variable`, { key, value });

    return [ Transformers.toServerEggVariable(data), data.meta.startup_command ];
};

const getServerResourceUsage = async (server: string): Promise<ServerStats> => {
    const { data } = await http.get(`/api/client/servers/${server}/resources`);
    const { attributes } = data;

    return {
        status: attributes.current_state,
        isSuspended: attributes.is_suspended,
        memoryUsageInBytes: attributes.resources.memory_bytes,
        cpuUsagePercent: attributes.resources.cpu_absolute,
        diskUsageInBytes: attributes.resources.disk_bytes,
        networkRxInBytes: attributes.resources.network_rx_bytes,
        networkTxInBytes: attributes.resources.network_tx_bytes,
        uptime: attributes.resources.uptime,
    };
};

export {
    getServer,
    getWebsocketToken,
    renameServer,
    reinstallServer,
    setSelectedDockerImage,
    updateStartupVariable,
    getServerResourceUsage,
};
