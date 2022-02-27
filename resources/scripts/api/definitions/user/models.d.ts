import { Model, UUID } from '@/api/definitions';

interface SecurityKey extends Model {
    uuid: UUID;
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
