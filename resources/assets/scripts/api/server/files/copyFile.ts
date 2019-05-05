import http from "@/api/http";

/**
 * Creates a copy of the given file or directory on the Daemon. Expects a fully resolved path
 * to be passed through for both data arguments.
 */
export function copyFile(server: string, location: string): Promise<void> {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${server}/files/copy`, {location})
            .then(() => resolve())
            .catch(reject);
    });
}
