import {ServerApplicationCredentials} from "@/store/types";
import {withCredentials} from "@/api/http";

/**
 * Connects to the remote daemon and creates a new folder on the server.
 */
export function createFolder(server: string, credentials: ServerApplicationCredentials, path: string): Promise<void> {
    return new Promise((resolve, reject) => {
        withCredentials(server, credentials).post('/v1/server/file/folder', { path })
            .then(() => resolve())
            .catch(reject);
    });
}
