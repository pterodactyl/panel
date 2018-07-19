import Vue from 'vue';
import Vuex from 'vuex';
import auth from './modules/auth';
import dashboard from './modules/dashboard';
import server from './modules/server';

Vue.use(Vuex);

const store = new Vuex.Store({
    strict: process.env.NODE_ENV !== 'production',
    modules: {auth, dashboard, server},
});

if (module.hot) {
    module.hot.accept(['./modules/auth'], () => {
        const newAuthModule = require('./modules/auth').default;
        const newDashboardModule = require('./modules/dashboard').default;
        const newServerModule = require('./modules/server').default;

        store.hotUpdate({
            modules: {newAuthModule, newDashboardModule, newServerModule},
        });
    });
}

export default store;
