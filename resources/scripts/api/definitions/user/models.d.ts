// eslint-disable-next-line @typescript-eslint/no-empty-interface
export interface Model {}

interface SecurityKey extends Model {
    uuid: string;
    name: string;
    type: 'public-key';
    publicKeyId: string;
    createdAt: Date;
    updatedAt: Date;
}

interface PersonalAccessToken extends Model {
    identifier: string;
    description: string;
    createdAt: Date;
    updatedAt: Date;
    lastUsedAt: Date | null;
}
