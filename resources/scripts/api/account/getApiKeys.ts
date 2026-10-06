import http from '@/api/http';

export interface ApiKey {
    identifier: string;
    description: string;
    allowedIps: string[];
    permissions: string[] | null;
    allowedServers: string[] | null;
    createdAt: Date | null;
    lastUsedAt: Date | null;
}

export const rawDataToApiKey = (data: any): ApiKey => ({
    identifier: data.identifier,
    description: data.description,
    allowedIps: data.allowed_ips,
    permissions: data.permissions ?? null,
    allowedServers: data.allowed_servers ?? null,
    createdAt: data.created_at ? new Date(data.created_at) : null,
    lastUsedAt: data.last_used_at ? new Date(data.last_used_at) : null,
});

export default (): Promise<ApiKey[]> => {
    return new Promise((resolve, reject) => {
        http.get('/api/client/account/api-keys')
            .then(({ data }) => resolve((data.data || []).map((d: any) => rawDataToApiKey(d.attributes))))
            .catch(reject);
    });
};
