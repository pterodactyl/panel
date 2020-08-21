import { FractalResponseData } from '@/api/http';
import { ServerBackup } from '@/api/server/types';

export const rawDataToServerBackup = ({ attributes }: FractalResponseData): ServerBackup => ({
    uuid: attributes.uuid,
    isSuccessful: attributes.is_successful,
    name: attributes.name,
    ignoredFiles: attributes.ignored_files,
    sha256Hash: attributes.sha256_hash,
    bytes: attributes.bytes,
    createdAt: new Date(attributes.created_at),
    completedAt: attributes.completed_at ? new Date(attributes.completed_at) : null,
});
