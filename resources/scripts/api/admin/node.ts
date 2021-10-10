import { Model, UUID, WithRelationships, withRelationships } from '@/api/admin/index';
import { Location } from '@/api/admin/location';
import http from '@/api/http';
import { AdminTransformers } from '@/api/admin/transformers';
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
            include: [ 'location' ],
        },
    });

    return withRelationships(AdminTransformers.toNode(data.data), 'location');
};
