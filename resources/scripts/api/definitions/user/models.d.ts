import { Model, UUID } from '@/api/definitions';
import { ServerEggVariable, ServerStatus } from '@/api/server/types';

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

interface Allocation extends Model {
    id: number;
    ip: string;
    alias: string | null;
    port: number;
    notes: string | null;
    isDefault: boolean;
}

interface Server extends Model {
    id: string;
    internalId: number | string;
    uuid: UUID;
    name: string;
    node: string;
    status: ServerStatus;
    sftpDetails: {
        ip: string;
        port: number;
    };
    invocation: string;
    dockerImage: string;
    description: string;
    limits: {
        memory: number;
        swap: number;
        disk: number;
        io: number;
        cpu: number;
        threads: string;
    };
    eggFeatures: string[];
    featureLimits: {
        databases: number;
        allocations: number;
        backups: number;
    };
    isInstalling: boolean;
    isTransferring: boolean;
    variables: ServerEggVariable[];
    allocations: Allocation[];
}
