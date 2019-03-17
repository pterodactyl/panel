import http from "@/api/http";
// @ts-ignore
import route from '../../../../../../vendor/tightenco/ziggy/src/js/route';

/**
 * Gets a download token for a file on the server.
 */
export function getDownloadToken(server: string, file: string): Promise<string | null> {
    return new Promise((resolve, reject) => {
        http.post(route('api.client.servers.files.download', { server, file }))
            .then(response => resolve(response.data ? response.data.token || null : null))
            .catch(reject);
    });
}
