import http from "@/api/http";

/**
 * Connects to the remote daemon and creates a new folder on the server.
 */
export function createFolder(server: string, directory: string, name: string): Promise<void> {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${server}/files/create-folder`, {
            directory, name,
        })
            .then(() => resolve())
            .catch(reject);
    });
}
