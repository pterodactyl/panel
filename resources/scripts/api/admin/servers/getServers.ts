import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';
import { createContext, useContext } from 'react';
import useSWR from 'swr';
import { Node, rawDataToNode } from '@/api/admin/nodes/getNodes';
import { User, rawDataToUser } from '@/api/admin/users/getUsers';

export interface Server {
    id: number;
    externalId: string;
    uuid: string;
    identifier: string;
    name: string;
    description: string;
    isSuspended: boolean;
    isInstalling: boolean;
    isTransferring: boolean;
    createdAt: Date;
    updatedAt: Date;

    relations: {
        node: Node | undefined;
        user: User | undefined;
    };
}

const rawDataToServerObject = ({ attributes }: FractalResponseData): Server => ({
    id: attributes.id,
    externalId: attributes.external_id,
    uuid: attributes.uuid,
    identifier: attributes.identifier,
    name: attributes.name,
    description: attributes.description,
    isSuspended: attributes.is_suspended,
    isInstalling: attributes.is_installing,
    isTransferring: attributes.is_transferring,
    createdAt: new Date(attributes.created_at),
    updatedAt: new Date(attributes.updated_at),

    relations: {
        node: attributes.relationships?.node !== undefined ? rawDataToNode(attributes.relationships.node as FractalResponseData) : undefined,
        user: attributes.relationships?.user !== undefined ? rawDataToUser(attributes.relationships.user as FractalResponseData) : undefined,
    },
});

interface ctx {
    page: number;
    setPage: (value: number | ((s: number) => number)) => void;
}

export const Context = createContext<ctx>({ page: 1, setPage: () => 1 });

export default (include: string[] = []) => {
    const { page } = useContext(Context);

    return useSWR<PaginatedResult<Server>>([ 'servers', page ], async () => {
        const { data } = await http.get('/api/application/servers', { params: { include: include.join(','), page } });

        return ({
            items: (data.data || []).map(rawDataToServerObject),
            pagination: getPaginationSet(data.meta.pagination),
        });
    });
};
