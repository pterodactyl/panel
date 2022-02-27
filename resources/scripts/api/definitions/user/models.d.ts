import { Model, UUID } from '@/api/definitions';

export type ServerStatus = 'installing' | 'install_failed' | 'suspended' | 'restoring_backup' | null;

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

interface ServerBackup extends Model {
    uuid: UUID;
    isSuccessful: boolean;
    isLocked: boolean;
    name: string;
    ignoredFiles: string;
    checksum: string;
    bytes: number;
    createdAt: Date;
    completedAt: Date | null;
}

interface ServerEggVariable extends Model {
    name: string;
    description: string;
    envVariable: string;
    defaultValue: string;
    serverValue: string;
    isEditable: boolean;
    rules: string[];
}

interface ServerDatabase extends Model {
    id: string;
    name: string;
    username: string;
    connectionString: string;
    allowConnectionsFrom: string;
    password?: string;
}

export type SubuserPermission =
    'websocket.connect' |
    'control.console' | 'control.start' | 'control.stop' | 'control.restart' |
    'user.create' | 'user.read' | 'user.update' | 'user.delete' |
    'file.create' | 'file.read' | 'file.update' | 'file.delete' | 'file.archive' | 'file.sftp' |
    'allocation.read' | 'allocation.update' |
    'startup.read' | 'startup.update' |
    'database.create' | 'database.read' | 'database.update' | 'database.delete' | 'database.view_password' |
    'schedule.create' | 'schedule.read' | 'schedule.update' | 'schedule.delete'
    ;

interface Subuser extends Model {
    uuid: string;
    username: string;
    email: string;
    image: string;
    twoFactorEnabled: boolean;
    createdAt: Date;
    permissions: SubuserPermission[];

    can (permission: SubuserPermission): boolean;
}
