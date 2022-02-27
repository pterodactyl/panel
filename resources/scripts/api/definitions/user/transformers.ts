import * as Models from './models';
import { FractalResponseData, FractalResponseList } from '@/api/http';
import { rawDataToServerEggVariable } from '@/api/transformers';

export default class Transformers {
    static toSecurityKey ({ attributes }: FractalResponseData): Models.SecurityKey {
        return {
            uuid: attributes.uuid,
            name: attributes.name,
            type: attributes.type,
            publicKeyId: attributes.public_key_id,
            createdAt: new Date(attributes.created_at),
            updatedAt: new Date(attributes.updated_at),
        };
    }

    static toPersonalAccessToken ({ attributes }: FractalResponseData): Models.PersonalAccessToken {
        return {
            identifier: attributes.token_id,
            description: attributes.description,
            createdAt: new Date(attributes.created_at),
            updatedAt: new Date(attributes.updated_at),
            lastUsedAt: attributes.last_used_at ? new Date(attributes.last_used_at) : null,
        };
    }

    static toServerAllocation ({ attributes }: FractalResponseData): Models.Allocation {
        return {
            id: attributes.id,
            ip: attributes.ip,
            alias: attributes.ip_alias,
            port: attributes.port,
            notes: attributes.notes,
            isDefault: attributes.is_default,
        };
    }

    static toServer ({ attributes }: FractalResponseData): Models.Server {
        return {
            id: attributes.identifier,
            internalId: attributes.internal_id,
            uuid: attributes.uuid,
            name: attributes.name,
            node: attributes.node,
            status: attributes.status,
            invocation: attributes.invocation,
            dockerImage: attributes.docker_image,
            sftpDetails: {
                ip: attributes.sftp_details.ip,
                port: attributes.sftp_details.port,
            },
            description: attributes.description ? ((attributes.description.length > 0) ? attributes.description : null) : null,
            limits: { ...attributes.limits },
            eggFeatures: attributes.egg_features || [],
            featureLimits: { ...attributes.feature_limits },
            isInstalling: attributes.status === 'installing' || attributes.status === 'install_failed',
            isTransferring: attributes.is_transferring,
            variables: ((attributes.relationships?.variables as FractalResponseList | undefined)?.data || []).map(rawDataToServerEggVariable),
            allocations: ((attributes.relationships?.allocations as FractalResponseList | undefined)?.data || []).map(this.toServerAllocation),
        };
    }
}
