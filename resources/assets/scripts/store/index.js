import Vue from 'vue';
import Vuex from 'vuex';
import server from "./modules/server";
import auth from "./modules/auth";

Vue.use(Vuex);

const store = new Vuex.Store({
    strict: process.env.NODE_ENV !== 'production',
    modules: { auth, server },
});

if (module.hot) {
    module.hot.accept(['./modules/auth'], () => {
        const newAuthModule = require('./modules/auth').default;
        const newServerModule = require('./modules/server').default;

        store.hotUpdate({
            modules: { newAuthModule, newServerModule },
        });
    });
}

export default store;
