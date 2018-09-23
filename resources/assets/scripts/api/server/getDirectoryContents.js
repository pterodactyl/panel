// @flow
import http from './../http';
import filter from 'lodash/filter';
import isObject from 'lodash/isObject';
import route from '../../../../../vendor/tightenco/ziggy/src/js/route';

export interface DirectoryContentsResponse {
    files: Object,
    directories: Object,
    editable: Array<string>,
}

/**
 * Get the contents of a specific directory for a given server.
 *
 * @param {String} server
 * @param {String} directory
 * @return {Promise<DirectoryContentsResponse>}
 */
export function getDirectoryContents(server: string, directory: string): Promise<DirectoryContentsResponse> {
    return new Promise((resolve, reject) => {
        http.get(route('server.files', { server, directory }))
            .then((response) => {
                return resolve({
                    files: filter(response.data.contents, function (o) {
                        return o.file;
                    }),
                    directories: filter(response.data.contents, function (o) {
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
                    err.response.data.errors.forEach(error => {
                        return reject(error.detail);
                    });
                }

                return reject(err);
            });
    });
}

export default getDirectoryContents;
