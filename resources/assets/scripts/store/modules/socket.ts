import Status from '../../helpers/statuses';
import {SocketState} from "../types";

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

        SOCKET_ERROR: (state: SocketState, err: Error) => {
            state.connected = false;
            state.connectionError = err;
        },

        'SOCKET_INITIAL STATUS': (state: SocketState, data: string) => {
            state.status = data;
        },

        SOCKET_STATUS: (state: SocketState, data: string) => {
            state.status = data;
        }
    },
};
