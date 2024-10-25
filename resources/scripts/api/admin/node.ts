import { Model, UUID, WithRelationships, withRelationships } from '@/api/admin/index';
import { Location } from '@/api/admin/location';
import http, { QueryBuilderParams, withQueryBuilderParams } from '@/api/http';
import { Transformers } from '@definitions/admin';
import { Server } from '@/api/admin/server';

interface NodePorts {
    http: {
        listen: number;
        public: number;
    };
    sftp: {
        listen: number;
        public: number;
    };
}

export interface Allocation extends Model {
    id: number;
    ip: string;
    port: number;
    alias: string | null;
    isAssigned: boolean;
    relationships: {
        node?: Node;
        server?: Server | null;
    };
    getDisplayText(): string;
}

export interface Node extends Model {
    id: number;
    uuid: UUID;
    isPublic: boolean;
    locationId: number;
    databaseHostId: number;
    name: string;
    description: string | null;
    fqdn: string;
    ports: NodePorts;
    scheme: 'http' | 'https';
    isBehindProxy: boolean;
    isMaintenanceMode: boolean;
    memory: number;
    memoryOverallocate: number;
    disk: number;
    diskOverallocate: number;
    uploadSize: number;
    daemonBase: string;
    createdAt: Date;
    updatedAt: Date;
    relationships: {
        location?: Location;
    };
}

/**
 * Gets a single node and returns it.
 */
export const getNode = async (id: string | number): Promise<WithRelationships<Node, 'location'>> => {
    const { data } = await http.get(`/api/application/nodes/${id}`, {
        params: {
            include: ['location'],
        },
    });

    return withRelationships(Transformers.toNode(data.data), 'location');
};

export const searchNodes = async (params: QueryBuilderParams<'name'>): Promise<Node[]> => {
    const { data } = await http.get('/api/application/nodes', {
        params: withQueryBuilderParams(params),
    });

    return data.data.map(Transformers.toNode);
};

export const getAllocations = async (
    id: string | number,
    params?: QueryBuilderParams<'ip' | 'server_id'>,
): Promise<Allocation[]> => {
    const { data } = await http.get(`/api/application/nodes/${id}/allocations`, {
        params: withQueryBuilderParams(params),
    });

    return data.data.map(Transformers.toAllocation);
};
