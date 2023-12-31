export type ServerStatus =
    | 'installing'
    | 'install_failed'
    | 'reinstall_failed'
    | 'suspended'
    | 'restoring_backup'
    | null;

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
    serverValue: string | null;
    isEditable: boolean;
    rules: string[];
}
