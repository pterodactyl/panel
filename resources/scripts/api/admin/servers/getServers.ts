import { Allocation, rawDataToAllocation } from '@/api/admin/nodes/getAllocations';
import { useContext } from 'react';
import useSWR from 'swr';
import { createContext } from '@/api/admin';
import http, { FractalResponseData, FractalResponseList, getPaginationSet, PaginatedResult } from '@/api/http';
import { Egg, rawDataToEgg } from '@/api/admin/eggs/getEgg';
import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes';
import { Transformers, User } from '@definitions/admin';

export interface ServerVariable {
    id: number;
    eggId: number;
    name: string;
    description: string;
    envVariable: string;
    defaultValue: string;
    userViewable: boolean;
    userEditable: boolean;
    rules: string;
    required: boolean;
    serverValue: string;
    createdAt: Date;
    updatedAt: Date;
}

export const rawDataToServerVariable = ({ attributes }: FractalResponseData): ServerVariable => ({
    id: attributes.id,
    eggId: attributes.egg_id,
    name: attributes.name,
    description: attributes.description,
    envVariable: attributes.env_variable,
    defaultValue: attributes.default_value,
    userViewable: attributes.user_viewable,
    userEditable: attributes.user_editable,
    rules: attributes.rules,
    required: attributes.required,
    serverValue: attributes.server_value,
    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),
});

export interface Server {
    id: number;
    externalId: string | null;
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
        oomKiller: boolean;
    };

    featureLimits: {
        databases: number;
        allocations: number;
        backups: number;
    };

    ownerId: number;
    nodeId: number;
    allocationId: number;
    nestId: number;
    eggId: number;

    container: {
        startup: string;
        image: string;
        environment: Map<string, string>;
    };

    createdAt: Date;
    updatedAt: Date;

    relations: {
        allocations?: Allocation[];
        egg?: Egg;
        node?: Node;
        user?: User;
        variables: ServerVariable[];
    };
}

export const rawDataToServer = ({ attributes }: FractalResponseData): Server =>
    ({
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
            oomKiller: attributes.limits.oom_killer,
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
            startup: attributes.container.startup,
            image: attributes.container.image,
            environment: attributes.container.environment,
        },

        createdAt: new Date(attributes.created_at),
        updatedAt: new Date(attributes.updated_at),

        relations: {
            allocations: ((attributes.relationships?.allocations as FractalResponseList | undefined)?.data || []).map(
                rawDataToAllocation,
            ),
            egg:
                attributes.relationships?.egg?.object === 'egg'
                    ? rawDataToEgg(attributes.relationships.egg as FractalResponseData)
                    : undefined,
            node:
                attributes.relationships?.node?.object === 'node'
                    ? rawDataToNode(attributes.relationships.node as FractalResponseData)
                    : undefined,
            user:
                attributes.relationships?.user?.object === 'user'
                    ? Transformers.toUser(attributes.relationships.user as FractalResponseData)
                    : undefined,
            variables: ((attributes.relationships?.variables as FractalResponseList | undefined)?.data || []).map(
                rawDataToServerVariable,
            ),
        },
    } as Server);

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
            // @ts-expect-error todo
            params['filter[' + key + ']'] = filters[key];
        });
    }

    if (sort !== null) {
        // @ts-expect-error todo
        params.sort = (sortDirection ? '-' : '') + sort;
    }

    return useSWR<PaginatedResult<Server>>(['servers', page, filters, sort, sortDirection], async () => {
        const { data } = await http.get('/api/application/servers', {
            params: { include: include.join(','), page, ...params },
        });

        return {
            items: (data.data || []).map(rawDataToServer),
            pagination: getPaginationSet(data.meta.pagination),
        };
    });
};
