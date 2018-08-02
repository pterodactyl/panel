import Status from './../../helpers/statuses';

export default {
    namespaced: true,
    state: {
        connected: false,
        connectionError: null,
        status: Status.STATUS_OFF,
    },
    actions: {
    },
    mutations: {
        SOCKET_CONNECT: (state) => {
            state.connected = true;
        },

        SOCKET_ERROR: (state, err) => {
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
