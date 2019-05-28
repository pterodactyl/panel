import Status from '../../helpers/statuses';
import {SocketState} from "../types";

export default {
    namespaced: true,
    state: {
        connected: false,
        connectionError: false,
        status: Status.STATUS_OFF,
        outputBuffer: [],
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
        },

        'SOCKET_CONSOLE OUTPUT': (state: SocketState, data: string) => {
            const { outputBuffer } = state;

            if (outputBuffer.length >= 500) {
                // Pop all of the output buffer items off the front until we only have 499
                // items in the array.
                for (let i = 0; i <= (outputBuffer.length - 500); i++) {
                    outputBuffer.shift();
                    i++;
                }
            }

            outputBuffer.push(data);
            state.outputBuffer = outputBuffer;
        },
    },
};
