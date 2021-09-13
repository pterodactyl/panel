import { Server, rawDataToServer } from '@/api/admin/servers/getServers';
import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { useContext } from 'react';
import useSWR from 'swr';
import { createContext } from '@/api/admin';

export interface Allocation {
    id: number;
    ip: string;
    port: number;
    alias: string | null;
    serverId: number | null;
    assigned: boolean;

    relations: {
        server?: Server;
    }
}

export const rawDataToAllocation = ({ attributes }: FractalResponseData): Allocation => ({
    id: attributes.id,
    ip: attributes.ip,
    port: attributes.port,
    alias: attributes.ip_alias || null,
    serverId: attributes.server_id,
    assigned: attributes.assigned,

    relations: {
        server: attributes.relationships?.server?.object === 'server' ? rawDataToServer(attributes.relationships.server as FractalResponseData) : undefined,
    },
});

export interface Filters {
    id?: string;
    ip?: string;
    port?: string;
}

export const Context = createContext<Filters>();

export default (id: string | number, include: string[] = []) => {
    const { page, filters, sort, sortDirection } = useContext(Context);

    const params = {};
    if (filters !== null) {
        Object.keys(filters).forEach(key => {
            // @ts-ignore
            params['filter[' + key + ']'] = filters[key];
        });
    }

    if (sort !== null) {
        // @ts-ignore
        params.sort = (sortDirection ? '-' : '') + sort;
    }

    return useSWR<PaginatedResult<Allocation>>([ 'allocations', page, filters, sort, sortDirection ], async () => {
        const { data } = await http.get(`/api/application/nodes/${id}/allocations`, { params: { include: include.join(','), page, ...params } });

        return ({
            items: (data.data || []).map(rawDataToAllocation),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
