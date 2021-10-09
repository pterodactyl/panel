/* eslint-disable camelcase */
import { Server } from '@/api/admin/server';
import { FractalResponseData, FractalResponseList } from '@/api/http';
import { rawDataToAllocation } from '@/api/admin/nodes/getAllocations';
import { rawDataToEgg } from '@/api/admin/eggs/getEgg';
import { rawDataToNode } from '@/api/admin/nodes/getNodes';
import { rawDataToUser } from '@/api/admin/users/getUsers';
import { rawDataToServerVariable } from '@/api/admin/servers/getServers';

const isList = (data: FractalResponseList | FractalResponseData): data is FractalResponseList => data.object === 'list';

function transform<T, M = undefined> (data: undefined, transformer: (callback: FractalResponseData) => T, missing?: M): undefined;
function transform<T, M> (data: FractalResponseData | undefined, transformer: (callback: FractalResponseData) => T, missing?: M): T | M | undefined;
function transform<T, M> (data: FractalResponseList | undefined, transformer: (callback: FractalResponseData) => T, missing?: M): T[] | undefined;
function transform<T> (data: FractalResponseData | FractalResponseList | undefined, transformer: (callback: FractalResponseData) => T, missing = undefined) {
    if (data === undefined) return undefined;

    if (isList(data)) {
        return data.data.map(transformer);
    }

    return !data ? missing : transformer(data);
}

export class AdminTransformers {
    static toServer = ({ attributes }: FractalResponseData): Server => {
        const { oom_disabled, ...limits } = attributes.limits;
        const { allocations, egg, node, user, variables } = attributes.relationships || {};

        return {
            id: attributes.id,
            uuid: attributes.uuid,
            externalId: attributes.external_id,
            identifier: attributes.identifier,
            name: attributes.name,
            description: attributes.description,
            status: attributes.status,
            userId: attributes.owner_id,
            nodeId: attributes.node_id,
            allocationId: attributes.allocation_id,
            eggId: attributes.egg_id,
            limits: { ...limits, oomDisabled: oom_disabled },
            featureLimits: attributes.feature_limits,
            container: attributes.container,
            createdAt: new Date(attributes.created_at),
            updatedAt: new Date(attributes.updated_at),
            relationships: {
                allocations: transform(allocations as FractalResponseList | undefined, rawDataToAllocation),
                egg: transform(egg as FractalResponseData | undefined, rawDataToEgg),
                node: transform(node as FractalResponseData | undefined, rawDataToNode),
                user: transform(user as FractalResponseData | undefined, rawDataToUser),
                variables: transform(variables as FractalResponseList | undefined, rawDataToServerVariable),
            },
        };
    };
}
