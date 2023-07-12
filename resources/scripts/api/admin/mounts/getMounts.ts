import http, { FractalResponseData, FractalResponseList, getPaginationSet, PaginatedResult } from '@/api/http';
import { useContext } from 'react';
import useSWR from 'swr';
import { createContext } from '@/api/admin';
import { Egg, rawDataToEgg } from '@/api/admin/eggs/getEgg';
import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes';
import { Server, rawDataToServer } from '@/api/admin/servers/getServers';

export interface Mount {
    id: number;
    uuid: string;
    name: string;
    description?: string;
    source: string;
    target: string;
    readOnly: boolean;
    userMountable: boolean;
    createdAt: Date;
    updatedAt: Date;

    relations: {
        eggs: Egg[] | undefined;
        nodes: Node[] | undefined;
        servers: Server[] | undefined;
    };
}

export const rawDataToMount = ({ attributes }: FractalResponseData): Mount => ({
    id: attributes.id,
    uuid: attributes.uuid,
    name: attributes.name,
    description: attributes.description,
    source: attributes.source,
    target: attributes.target,
    readOnly: attributes.read_only,
    userMountable: attributes.user_mountable,
    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),

    relations: {
        eggs: ((attributes.relationships?.eggs as FractalResponseList | undefined)?.data || []).map(rawDataToEgg),
        nodes: ((attributes.relationships?.nodes as FractalResponseList | undefined)?.data || []).map(rawDataToNode),
        servers: ((attributes.relationships?.servers as FractalResponseList | undefined)?.data || []).map(
            rawDataToServer,
        ),
    },
});

export interface Filters {
    id?: string;
    name?: string;
    source?: string;
    target?: string;
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

    return useSWR<PaginatedResult<Mount>>(['mounts', page, filters, sort, sortDirection], async () => {
        const { data } = await http.get('/api/application/mounts', {
            params: { include: include.join(','), page, ...params },
        });

        return {
            items: (data.data || []).map(rawDataToMount),
            pagination: getPaginationSet(data.meta.pagination),
        };
    });
};
