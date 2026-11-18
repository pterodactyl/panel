import http from '@/api/http';
import { ApiKey, rawDataToApiKey } from '@/api/account/getApiKeys';

export default (
    description: string,
    allowedIps: string,
    permissions?: string[] | null,
    allowedServers?: string[] | null
): Promise<ApiKey & { secretToken: string }> => {
    return new Promise((resolve, reject) => {
        http.post('/api/client/account/api-keys', {
            description,
            allowed_ips: allowedIps.length > 0 ? allowedIps.split('\n') : [],
            // eslint-disable-next-line camelcase
            permissions: permissions && permissions.length > 0 ? permissions : null,
            // eslint-disable-next-line camelcase
            allowed_servers: allowedServers && allowedServers.length > 0 ? allowedServers : null,
        })
            .then(({ data }) =>
                resolve({
                    ...rawDataToApiKey(data.attributes),
                    // eslint-disable-next-line camelcase
                    secretToken: data.meta?.secret_token ?? '',
                })
            )
            .catch(reject);
    });
};
