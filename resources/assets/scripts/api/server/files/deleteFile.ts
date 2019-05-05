import http from "@/api/http";

/**
 * Deletes files and/or folders from the server. You should pass through an array of
 * file or folder paths to be deleted.
 */
export function deleteFile(server: string, location: string): Promise<void> {
    return new Promise((resolve, reject) => {
        http.post(`/api/client/servers/${server}/files/delete`, {location})
            .then(() => resolve())
            .catch(reject);
    })
}
