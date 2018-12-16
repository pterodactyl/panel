import Vue from 'vue';
import Vuex from 'vuex';
import auth, {AuthenticationState} from './modules/auth';
import dashboard, {DashboardState} from './modules/dashboard';
import server, {ServerState} from './modules/server';
import socket, {SocketState} from './modules/socket';

Vue.use(Vuex);

export type ApplicationState = {
    socket: SocketState,
    server: ServerState,
    auth: AuthenticationState,
    dashboard: DashboardState,
}

const store = new Vuex.Store({
    strict: process.env.NODE_ENV !== 'production',
    modules: {auth, dashboard, server, socket},
});

if (module.hot) {
    module.hot.accept(['./modules/auth'], () => {
        const newAuthModule = require('./modules/auth').default;
        const newDashboardModule = require('./modules/dashboard').default;
        const newServerModule = require('./modules/server').default;
        const newSocketModule = require('./modules/socket').default;

        store.hotUpdate({
            modules: {
                auth: newAuthModule,
                dashboard: newDashboardModule,
                server: newServerModule,
                socket: newSocketModule
            },
        });
    });
}

export default store;
