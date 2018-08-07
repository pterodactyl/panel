import Status from './../../helpers/statuses';

export default {
    namespaced: true,
    state: {
        connected: false,
        connectionError: false,
        status: Status.STATUS_OFF,
    },
    actions: {
    },
    mutations: {
        SOCKET_CONNECT: (state) => {
            state.connected = true;
            state.connectionError = false;
        },

        SOCKET_ERROR: (state, err) => {
            state.connected = false;
            state.connectionError = err;
        },

        SOCKET_CONNECT_ERROR: (state, err) => {
            state.connected = false;
            state.connectionError = err;
        },

        'SOCKET_INITIAL STATUS': (state, data) => {
            state.status = data.status;
        },

        SOCKET_STATUS: (state, data) => {
            state.status = data.status;
        }
    },
};
