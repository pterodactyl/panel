// @ts-ignore
import route from '../../../../../vendor/tightenco/ziggy/src/js/route';
import {ActionContext} from "vuex";
import {ServerData} from "../../models/server";

type ServerApplicationCredentials = {
    node: string,
    key: string,
};

export type ServerState = {
    server: ServerData,
    credentials: ServerApplicationCredentials,
    console: Array<string>,
};

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
         * Fetches the active server from the API and stores it in vuex.
         */
        getServer: ({commit}: ActionContext<ServerState, any>, {server}: { server: string }): Promise<void> => {
            return new Promise((resolve, reject) => {
                // @ts-ignore
                window.axios.get(route('api.client.servers.view', { server }))
                    // @ts-ignore
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
         */
        getCredentials: ({commit}: ActionContext<ServerState, any>, {server}: { server: string }) => {
            return new Promise((resolve, reject) => {
                // @ts-ignore
                window.axios.get(route('server.credentials', {server}))
                    // @ts-ignore
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
        SERVER_DATA: function (state: ServerState, data: ServerData) {
            state.server = data;
        },
        SERVER_CREDENTIALS: function (state: ServerState, credentials: ServerApplicationCredentials) {
            state.credentials = credentials;
        },
        CONSOLE_DATA: function (state: ServerState, data: string) {
            state.console.push(data);
        },
    },
}
