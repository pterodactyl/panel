import http from './../http';
import filter from 'lodash/filter';
import isObject from 'lodash/isObject';
import route from '../../../../../vendor/tightenco/ziggy/src/js/route';

/**
 * Get the contents of a specific directory for a given server.
 *
 * @param {String} server
 * @param {String} directory
 * @return {Promise}
 */
export function getDirectoryContents (server, directory) {
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
