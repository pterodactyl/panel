import Status from '../../helpers/statuses';

export type SocketState = {
    connected: boolean,
    connectionError: boolean | Error,
    status: number,
}

export default {
    namespaced: true,
    state: {
        connected: false,
        connectionError: false,
        status: Status.STATUS_OFF,
    },
    mutations: {
        SOCKET_CONNECT: (state: SocketState) => {
            state.connected = true;
            state.connectionError = false;
        },

        SOCKET_ERROR: (state: SocketState, err : Error) => {
            state.connected = false;
            state.connectionError = err;
        },

        SOCKET_CONNECT_ERROR: (state: SocketState, err : Error) => {
            state.connected = false;
            state.connectionError = err;
        },

        'SOCKET_INITIAL STATUS': (state: SocketState, data: { status: number }) => {
            state.status = data.status;
        },

        SOCKET_STATUS: (state: SocketState, data: { status: number }) => {
            state.status = data.status;
        }
    },
};
