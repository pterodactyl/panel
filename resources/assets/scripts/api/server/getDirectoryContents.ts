import http from '../http';
import {filter, isObject} from 'lodash';
// @ts-ignore
import route from '../../../../../vendor/tightenco/ziggy/src/js/route';
import {DirectoryContentObject, DirectoryContents} from "./types";

/**
 * Get the contents of a specific directory for a given server.
 */
export function getDirectoryContents(server: string, directory: string): Promise<DirectoryContents> {
    return new Promise((resolve, reject) => {
        http.get(route('server.files', {server, directory}))
            .then((response) => {
                return resolve({
                    files: filter(response.data.contents, function (o: DirectoryContentObject) {
                        return o.file;
                    }),
                    directories: filter(response.data.contents, function (o: DirectoryContentObject) {
                        return o.directory;
                    }),
                    editable: response.data.editable,
                });
            })
            .catch(err => {
                if (err.response && err.response.status === 404) {
                    return reject('The directory you requested could not be located on the server');
                }

                if (err.response.data && isObject(err.response.data.errors)) {
                    err.response.data.errors.forEach((error: any) => {
                        return reject(error.detail);
                    });
                }

                return reject(err);
            });
    });
}

export default getDirectoryContents;
