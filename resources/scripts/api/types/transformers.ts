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
}
