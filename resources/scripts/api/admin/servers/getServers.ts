import { useContext } from 'react';
import useSWR from 'swr';
import { createContext } from '@/api/admin';
import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { Egg, rawDataToEgg } from '@/api/admin/eggs/getEgg';
import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes';
import { User, rawDataToUser } from '@/api/admin/users/getUsers';

export interface Server {
    id: number;
    externalId: string | null
    uuid: string;
    identifier: string;
    name: string;
    description: string;
    status: string;

    limits: {
        memory: number;
        swap: number;
        disk: number;
        io: number;
        cpu: number;
        threads: string | null;
    }

    featureLimits: {
        databases: number;
        allocations: number;
        backups: number;
    }

    ownerId: number;
    nodeId: number;
    allocationId: number;
    nestId: number;
    eggId: number;

    container: {
        startupCommand: string;
        defaultStartup: string;
        image: string;
        environment: Map<string, string>;
    }

    oomKiller: boolean;

    createdAt: Date;
    updatedAt: Date;

    relations: {
        egg?: Egg;
        node?: Node;
        user?: User;
    }
}

export const rawDataToServer = ({ attributes }: FractalResponseData): Server => ({
    id: attributes.id,
    externalId: attributes.external_id,
    uuid: attributes.uuid,
    identifier: attributes.identifier,
    name: attributes.name,
    description: attributes.description,
    status: attributes.status,

    limits: {
        memory: attributes.limits.memory,
        swap: attributes.limits.swap,
        disk: attributes.limits.disk,
        io: attributes.limits.io,
        cpu: attributes.limits.cpu,
        threads: attributes.limits.threads,
    },

    featureLimits: {
        databases: attributes.feature_limits.databases,
        allocations: attributes.feature_limits.allocations,
        backups: attributes.feature_limits.backups,
    },

    ownerId: attributes.owner_id,
    nodeId: attributes.node_id,
    allocationId: attributes.allocation_id,
    nestId: attributes.nest_id,
    eggId: attributes.egg_id,

    container: {
        startupCommand: attributes.container.startup_command,
        defaultStartup: '',
        image: attributes.container.image,
        environment: attributes.container.environment,
    },

    oomKiller: attributes.oom_killer,

    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),

    relations: {
        egg: attributes.relationships?.egg?.object === 'egg' ? rawDataToEgg(attributes.relationships.egg as FractalResponseData) : undefined,
        node: attributes.relationships?.node?.object === 'node' ? rawDataToNode(attributes.relationships.node as FractalResponseData) : undefined,
        user: attributes.relationships?.user?.object === 'user' ? rawDataToUser(attributes.relationships.user as FractalResponseData) : undefined,
    },
}) as Server;

export interface Filters {
    id?: string;
    uuid?: string;
    name?: string;
    /* eslint-disable camelcase */
    owner_id?: string;
    node_id?: string;
    external_id?: string;
    /* eslint-enable camelcase */
}

export const Context = createContext<Filters>();

export default (include: string[] = []) => {
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

    return useSWR<PaginatedResult<Server>>([ 'servers', page, filters, sort, sortDirection ], async () => {
        const { data } = await http.get('/api/application/servers', { params: { include: include.join(','), page, ...params } });

        return ({
            items: (data.data || []).map(rawDataToServer),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
