import {ServerData} from "../models/server";
import Server from "../models/server";
import User from "../models/user";

export type ApplicationState = {
    socket: SocketState,
    server: ServerState,
    auth: AuthenticationState,
    dashboard: DashboardState,
}

export type SocketState = {
    connected: boolean,
    connectionError: boolean | Error,
    status: number,
}

export type ServerApplicationCredentials = {
    node: string,
    key: string,
};

export type ServerState = {
    server: ServerData,
    credentials: ServerApplicationCredentials,
    console: Array<string>,
};

export type DashboardState = {
    searchTerm: string,
    servers: Array<Server>,
};


export type AuthenticationState = {
    user: null | User,
}
