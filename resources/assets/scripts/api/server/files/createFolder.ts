import {ServerApplicationCredentials} from "@/store/types";
import http from "@/api/http";
import {AxiosError, AxiosRequestConfig} from "axios";
import {ServerData} from "@/models/server";

/**
 * Connects to the remote daemon and creates a new folder on the server.
 */
export function createFolder(server: ServerData, credentials: ServerApplicationCredentials, path: string): Promise<void> {
    const config: AxiosRequestConfig = {
        baseURL: credentials.node,
        headers: {
            'X-Access-Server': server.uuid,
            'X-Access-Token': credentials.key,
        },
    };

    return new Promise((resolve, reject) => {
        http.post('/v1/server/file/folder', { path }, config)
            .then(() => {
                resolve();
            })
            .catch((error: AxiosError) => {
                reject(error);
            });
    });
}
