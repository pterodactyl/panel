import http, { FractalResponseData } from '@/api/http';
import { rawDataToServer, Server } from '@/api/admin/servers/getServers';

export interface Allocation {
    id: number;
    ip: string;
    port: number;
    alias: string | null;
    serverId: number | null;
    assigned: boolean;

    relations: {
        server?: Server;
    };

    getDisplayText(): string;
}

export const rawDataToAllocation = ({ attributes }: FractalResponseData): Allocation => ({
    id: attributes.id,
    ip: attributes.ip,
    port: attributes.port,
    alias: attributes.alias || null,
    serverId: attributes.server_id,
    assigned: attributes.assigned,

    relations: {
        server:
            attributes.relationships?.server?.object === 'server'
                ? rawDataToServer(attributes.relationships.server as FractalResponseData)
                : undefined,
    },

    // TODO: If IP is an IPv6, wrap IP in [].
    getDisplayText(): string {
        if (attributes.alias !== null) {
            return `${attributes.ip}:${attributes.port} (${attributes.alias})`;
        }
        return `${attributes.ip}:${attributes.port}`;
    },
});

export interface Filters {
    ip?: string;
    /* eslint-disable camelcase */
    server_id?: string;
    /* eslint-enable camelcase */
}

export default (id: string | number, filters: Filters = {}, include: string[] = []): Promise<Allocation[]> => {
    const params = {};
    if (filters !== null) {
        Object.keys(filters).forEach(key => {
            // @ts-expect-error todo
            params['filter[' + key + ']'] = filters[key];
        });
    }

    return new Promise((resolve, reject) => {
        http.get(`/api/application/nodes/${id}/allocations`, { params: { include: include.join(','), ...params } })
            .then(({ data }) => resolve((data.data || []).map(rawDataToAllocation)))
            .catch(reject);
    });
};
