import http from '@/api/http';
// @ts-ignore
import route from '../../../../../vendor/tightenco/ziggy/src/js/route';
import {AxiosError} from "axios";
import {ServerDatabase} from "@/api/server/types";

/**
 * Creates a new database on the system for the currently active server.
 */
export function createDatabase(server: string, database: string, remote: string): Promise<ServerDatabase> {
    return new Promise((resolve, reject) => {
        http.post(route('api.client.servers.databases', {server}), {database, remote})
            .then(response => {
                const copy: any = response.data.attributes;
                copy.password = copy.relationships.password.attributes.password;
                copy.showPassword = false;

                delete copy.relationships;

                resolve(copy);
            })
            .catch((err: AxiosError) => {
                if (err.response && err.response.data && Array.isArray(err.response.data.errors)) {
                    return reject(err.response.data.errors[0].detail);
                }

                return reject(err);
            });
    });
}
