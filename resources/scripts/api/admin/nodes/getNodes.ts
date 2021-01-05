import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { createContext, useContext } from 'react';
import useSWR from 'swr';

export interface Node {
    id: number;
    uuid: string;
    public: boolean;
    name: string;
    description: string | null;
    locationId: number;
    fqdn: string;
    scheme: string;
    behindProxy: boolean;
    maintenanceMode: boolean;
    memory: number;
    memoryOverallocate: number;
    disk: number;
    diskOverallocate: number;
    uploadSize: number;
    daemonListen: number;
    daemonSftp: number;
    daemonBase: string;
    createdAt: Date;
    updatedAt: Date;
}

export const rawDataToNode = ({ attributes }: FractalResponseData): Node => ({
    id: attributes.id,
    uuid: attributes.uuid,
    public: attributes.public,
    name: attributes.name,
    description: attributes.description,
    locationId: attributes.location_id,
    fqdn: attributes.fqdn,
    scheme: attributes.scheme,
    behindProxy: attributes.behind_proxy,
    maintenanceMode: attributes.maintenance_mode,
    memory: attributes.memory,
    memoryOverallocate: attributes.memory_overallocate,
    disk: attributes.disk,
    diskOverallocate: attributes.disk_overallocate,
    uploadSize: attributes.upload_size,
    daemonListen: attributes.daemon_listen,
    daemonSftp: attributes.daemon_sftp,
    daemonBase: attributes.daemon_base,
    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),
});

interface ctx {
    page: number;
    setPage: (value: number | ((s: number) => number)) => void;
}

export const Context = createContext<ctx>({ page: 1, setPage: () => 1 });

export default () => {
    const { page } = useContext(Context);

    return useSWR<PaginatedResult<Node>>([ 'nodes', page ], async () => {
        const { data } = await http.get('/api/application/nodes', { params: { page } });

        return ({
            items: (data.data || []).map(rawDataToNode),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
