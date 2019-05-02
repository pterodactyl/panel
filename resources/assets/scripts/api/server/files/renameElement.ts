import {withCredentials} from "@/api/http";
import {ServerApplicationCredentials} from "@/store/types";
import { join } from 'path';

type RenameObject = {
    path: string,
    fromName: string,
    toName: string,
}

/**
 * Renames a file or folder on the server using the node.
 */
export function renameElement(server: string, credentials: ServerApplicationCredentials, data: RenameObject): Promise<void> {
    return new Promise((resolve, reject) => {
        withCredentials(server, credentials).post('/v1/server/file/rename', {
            from: join(data.path, data.fromName),
            to: join(data.path, data.toName),
        })
            .then(() => resolve())
            .catch(reject);
    });
}
