import {withCredentials} from "@/api/http";
import {ServerApplicationCredentials} from "@/store/types";

type PathChangeObject = {
    currentPath: string,
    newPath: string,
}
/**
 * Creates a copy of the given file or directory on the Daemon. Expects a fully resolved path
 * to be passed through for both data arguments.
 */
export function copyElement(server: string, credentials: ServerApplicationCredentials, data: PathChangeObject, isMove = false): Promise<void> {
    return new Promise((resolve, reject) => {
        withCredentials(server, credentials).post(`/v1/server/file/${isMove ? 'move' : 'copy'}`, {
            from: data.currentPath,
            to: data.newPath,
        })
            .then(() => resolve())
            .catch(reject);
    });
}

/**
 * Moves a file or folder to a new location on the server. Works almost exactly the same as the copy
 * file logic, so it really just passes an extra argument to copy to indicate that it is a move.
 */
export function moveElement(server: string, credentials: ServerApplicationCredentials, data: PathChangeObject): Promise<void> {
    return copyElement(server, credentials, data, true);
}
