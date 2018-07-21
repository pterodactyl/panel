import route from '../../../../../vendor/tightenco/ziggy/src/js/route';

export default {
    namespaced: true,
    state: {
        server: {},
        credentials: {node: '', key: ''},
        console: [],
    },
    getters: {
    },
    actions: {
        /**
         *
         * @param commit
         * @param {String} server
         * @returns {Promise<any>}
         */
        getServer: ({commit}, {server}) => {
            return new Promise((resolve, reject) => {
                window.axios.get(route('api.client.servers.view', { server }))
                    .then(response => {
                        // If there is a 302 redirect or some other odd behavior (basically, response that isnt
                        // in JSON format) throw an error and don't try to continue with the login.
                        if (!(response.data instanceof Object)) {
                            return reject(new Error('An error was encountered while processing this request.'));
                        }

                        if (response.data.object === 'server' && response.data.attributes) {
                            commit('SERVER_DATA', response.data.attributes)
                        }

                        return resolve();
                    })
                    .catch(reject);
            });
        },

        /**
         * Get authentication credentials that the client should use when connecting to the daemon to
         * retrieve server information.
         *
         * @param commit
         * @param {String} server
         * @returns {Promise<any>}
         */
        getCredentials: ({commit}, {server}) => {
            return new Promise((resolve, reject) => {
                window.axios.get(route('server.credentials', {server}))
                    .then(response => {
                        // If there is a 302 redirect or some other odd behavior (basically, response that isnt
                        // in JSON format) throw an error and don't try to continue with the login.
                        if (!(response.data instanceof Object)) {
                            return reject(new Error('An error was encountered while processing this request.'));
                        }

                        if (response.data.key) {
                            commit('SERVER_CREDENTIALS', response.data)
                        }

                        return resolve();
                    })
                    .catch(reject);
            });
        },
    },
    mutations: {
        SERVER_DATA: function (state, data) {
            state.server = data;
        },
        SERVER_CREDENTIALS: function (state, credentials) {
            state.credentials = credentials;
        },
        CONSOLE_DATA: function (state, data) {
            state.console.push(data);
        },
    },
}
