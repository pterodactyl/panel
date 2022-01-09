export type ServerStatus = 'installing' | 'install_failed' | 'suspended' | 'restoring_backup' | null;

export interface ServerBackup {
    uuid: string;
    isSuccessful: boolean;
    isLocked: boolean;
    name: string;
    ignoredFiles: string;
    checksum: string;
    bytes: number;
    createdAt: Date;
    completedAt: Date | null;
}

export interface ServerEggVariable {
    name: string;
    description: string;
    envVariable: string;
    defaultValue: string;
    serverValue: string;
    isEditable: boolean;
    rules: string[];
}

export interface ServerAuditLog {
    uuid: string;
    user: string;
    action: string;
    device: Record<string, any>;
    metadata: Record<string, any>;
    isSystem: boolean;
    createdAt: Date;
}
