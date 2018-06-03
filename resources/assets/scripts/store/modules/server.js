import LoadingState from '../../models/loadingStates';
import route from '../../../../../vendor/tightenco/ziggy/src/js/route';

export const serverModule = {
    state: {
        servers: {},
        serverIDs: [],
        currentServerID: '',
        serverLoadingState: '',
    },
    mutations: {
        setCurrentServer (state, serverID) {
            state.currentServerID = serverID;
        },
        setServers (state, servers) {
            servers.forEach(s => {
                state.servers[s.identifier] = s;
                if (!!state.serverIDs.indexOf(s.identifier)) {
                    state.serverIDs.push(s.identifier)
                }
            });
        },
        removeServer (state, serverID) {
            delete state.servers[serverID];
            state.serverIDs.remove(serverID);
        },
        setServerLoadingState (state, s) {
            state.serverLoadingState = s;
        }
    },
    actions: {
        loadServers({ commit }) {
            commit('setServerLoadingState', LoadingState.LOADING);
            window.axios.get(route('api.client.index'))
                .then(response => {
                    commit('setServers', response.data.data.map(o => o.attributes));
                    commit('setServerLoadingState', LoadingState.DONE);
                })
                .catch(err => {
                    console.error(err);
                    const response = err.response;
                    if (response.data && _.isObject(response.data.errors)) {
                        response.data.errors.forEach(function (error) {
                            this.error(error.detail);
                        });
                    }
                })
                .finally(() => {
                    this.loading = false;
                });
        },
    },
    getters: {
        currentServer (state) {
            return state.servers[state.route.params.serverID];
        },
        isServersLoading (state) {
            return state.serverLoadingState === LoadingState.LOADING;
        },
        serverList (state) {
            return state.serverIDs.map(k => state.servers[k]);
        }
    }
};
