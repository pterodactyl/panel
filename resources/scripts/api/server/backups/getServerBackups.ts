import http, { FractalResponseData, getPaginationSet, PaginatedResult } from '@/api/http';

export interface ServerBackup {
    uuid: string;
    name: string;
    ignoredFiles: string;
    sha256Hash: string;
    bytes: number;
    createdAt: Date;
    completedAt: Date | null;
}

export const rawDataToServerBackup = ({ attributes }: FractalResponseData): ServerBackup => ({
    uuid: attributes.uuid,
    name: attributes.name,
    ignoredFiles: attributes.ignored_files,
    sha256Hash: attributes.sha256_hash,
    bytes: attributes.bytes,
    createdAt: new Date(attributes.created_at),
    completedAt: attributes.completed_at ? new Date(attributes.completed_at) : null,
});

export default (uuid: string, page?: number | string): Promise<PaginatedResult<ServerBackup>> => {
    return new Promise((resolve, reject) => {
        http.get(`/api/client/servers/${uuid}/backups`, { params: { page } })
            .then(({ data }) => resolve({
                items: (data.data || []).map(rawDataToServerBackup),
                pagination: getPaginationSet(data.meta.pagination),
            }))
            .catch(reject);
    });
};
