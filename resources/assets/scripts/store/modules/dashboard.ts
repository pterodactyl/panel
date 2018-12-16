import Server, {ServerData} from '../../models/server';
import {ActionContext} from "vuex";
const route = require('./../../../../../vendor/tightenco/ziggy/src/js/route').default;

export type DashboardState = {
    searchTerm: string,
    servers: Array<Server>,
};

export default {
    namespaced: true,
    state: {
        servers: [],
        searchTerm: '',
    },
    getters: {
        getSearchTerm: function (state: DashboardState): string {
            return state.searchTerm;
        },
    },
    actions: {
        /**
         * Retrieve all of the servers for a user matching the query.
         */
        loadServers: ({commit, state}: ActionContext<DashboardState, any>): Promise<void> => {
            return new Promise((resolve, reject) => {
                // @ts-ignore
                window.axios.get(route('api.client.index'), {
                    params: { query: state.searchTerm },
                })
                    // @ts-ignore
                    .then(response => {
                        // If there is a 302 redirect or some other odd behavior (basically, response that isnt
                        // in JSON format) throw an error and don't try to continue with the request processing.
                        if (!(response.data instanceof Object)) {
                            return reject(new Error('An error was encountered while processing this request.'));
                        }

                        // Remove all of the existing servers.
                        commit('clearServers');

                        response.data.data.forEach((obj: { attributes: ServerData }) => {
                            commit('addServer', obj.attributes);
                        });

                        resolve();
                    })
                    .catch(reject);
            });
        },

        setSearchTerm: ({commit}: ActionContext<DashboardState, any>, term: string) => {
            commit('setSearchTerm', term);
        },
    },
    mutations: {
        addServer: function (state: DashboardState, data: ServerData) {
            state.servers.push(
                new Server(data)
            );
        },
        clearServers: function (state: DashboardState) {
            state.servers = [];
        },
        setSearchTerm: function (state: DashboardState, term: string) {
            state.searchTerm = term;
        },
    },
};
