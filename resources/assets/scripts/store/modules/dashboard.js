import Server from './../../models/server';
const route = require('./../../../../../vendor/tightenco/ziggy/src/js/route').default;

export default {
    namespaced: true,
    state: {
        servers: [],
        searchTerm: '',
    },
    getters: {
        getSearchTerm: function (state) {
            return state.searchTerm;
        }
    },
    actions: {
        /**
         * Retrieve all of the servers for a user matching the query.
         *
         * @param commit
         * @param {String} query
         * @returns {Promise<any>}
         */
        loadServers: ({commit, state}) => {
            return new Promise((resolve, reject) => {
                window.axios.get(route('api.client.index'), {
                    params: { query: state.searchTerm },
                })
                    .then(response => {
                        // If there is a 302 redirect or some other odd behavior (basically, response that isnt
                        // in JSON format) throw an error and don't try to continue with the request processing.
                        if (!(response.data instanceof Object)) {
                            return reject(new Error('An error was encountered while processing this request.'));
                        }

                        // Remove all of the existing servers.
                        commit('clearServers');

                        response.data.data.forEach(obj => {
                            commit('addServer', obj.attributes);
                        });

                        resolve();
                    })
                    .catch(reject);
            });
        },

        setSearchTerm: ({commit}, term) => {
            commit('setSearchTerm', term);
        },
    },
    mutations: {
        addServer: function (state, data) {
            state.servers.push(new Server(data));
        },
        clearServers: function (state) {
            state.servers = [];
        },
        setSearchTerm: function (state, term) {
            state.searchTerm = term;
        },
    },
};
