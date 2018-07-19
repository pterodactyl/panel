import route from '../../../../../vendor/tightenco/ziggy/src/js/route';
import Server from '../../models/server';

export default {
    namespaced: true,
    state: {
        server: {},
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
    },
    mutations: {
        SERVER_DATA: function (state, data) {
            state.server = data;
        }
    },
}
