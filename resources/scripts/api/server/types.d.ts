export interface ServerBackup {
    uuid: string;
    isSuccessful: boolean;
    name: string;
    ignoredFiles: string;
    sha256Hash: string;
    bytes: number;
    createdAt: Date;
    completedAt: Date | null;
}
