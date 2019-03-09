import {withCredentials} from "@/api/http";
import {ServerApplicationCredentials} from "@/store/types";

/**
 * Deletes files and/or folders from the server. You should pass through an array of
 * file or folder paths to be deleted.
 */
export function deleteElement(server: string, credentials: ServerApplicationCredentials, items: Array<string>): Promise<any> {
    return new Promise((resolve, reject) => {
        withCredentials(server, credentials).post('/v1/server/file/delete', { items })
            .then(resolve)
            .catch(reject);
    })
}
