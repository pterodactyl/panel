import {withCredentials} from "@/api/http";
import {ServerApplicationCredentials} from "@/store/types";

/**
 * Creates a copy of the given file or directory on the Daemon. Expects a fully resolved path
 * to be passed through for both data arguments.
 */
export function copyElement(server: string, credentials: ServerApplicationCredentials, data: {
    currentPath: string, newPath: string
}): Promise<void> {
    return new Promise((resolve, reject) => {
        withCredentials(server, credentials).post('/v1/server/file/copy', {
            from: data.currentPath,
            to: data.newPath,
        })
            .then(() => resolve())
            .catch(reject);
    });
}
