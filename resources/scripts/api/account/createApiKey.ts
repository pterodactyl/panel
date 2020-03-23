import http from '@/api/http';
import { ApiKey, rawDataToApiKey } from '@/api/account/getApiKeys';

export default (description: string, allowedIps: string): Promise<ApiKey & { secretToken: string }> => {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/account/api-keys`, {
            description,
            // eslint-disable-next-line @typescript-eslint/camelcase
            allowed_ips: allowedIps.length > 0 ? allowedIps.split('\n') : [],
        })
            .then(({ data }) => resolve({
                ...rawDataToApiKey(data.attributes),
                secretToken: data.meta?.secret_token ?? '',
            }))
            .catch(reject);
    });
};
