import { SSHKey } from '@definitions/user/models';

export default class Transformers {
    static toSSHKey (data: Record<any, any>): SSHKey {
        return {
            name: data.name,
            publicKey: data.public_key,
            fingerprint: data.fingerprint,
            createdAt: new Date(data.created_at),
        };
    }
}

export class MetaTransformers {
}
