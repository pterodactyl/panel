import axios, {AxiosInstance, AxiosRequestConfig} from 'axios';
import {ServerApplicationCredentials} from "@/store/types";

// This token is set in the bootstrap.js file at the beginning of the request
// and is carried through from there.
// const token: string = '';

const http: AxiosInstance = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    },
});

// If we have a phpdebugbar instance registered at this point in time go
// ahead and route the response data through to it so things show up.
// @ts-ignore
if (typeof window.phpdebugbar !== 'undefined') {
    http.interceptors.response.use(response => {
        // @ts-ignore
        window.phpdebugbar.ajaxHandler.handle(response.request);

        return response;
    });
}

export default http;

/**
 * Creates a request object for the node that uses the server UUID and connection
 * credentials. Basically just a tiny wrapper to set this quickly.
 */
export function withCredentials(server: string, credentials: ServerApplicationCredentials): AxiosInstance {
    http.defaults.baseURL = credentials.node;
    http.defaults.headers['X-Access-Server'] = server;
    http.defaults.headers['X-Access-Token'] = credentials.key;

    return http;
}
