import * as Models from '@models';

export default class Transformers {
    static toSecurityKey (data: Record<string, any>): Models.SecurityKey {
        return {
            uuid: data.uuid,
            name: data.name,
            type: data.type,
            publicKeyId: data.public_key_id,
            createdAt: new Date(data.created_at),
            updatedAt: new Date(data.updated_at),
        };
    }

    static toPersonalAccessToken (data: Record<string, any>): Models.PersonalAccessToken {
        return {
            identifier: data.token_id,
            description: data.description,
            createdAt: new Date(data.created_at),
            updatedAt: new Date(data.updated_at),
            lastUsedAt: data.last_used_at ? new Date(data.last_used_at) : null,
        };
    }
}
