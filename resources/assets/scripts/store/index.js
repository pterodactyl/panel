import Vue from 'vue';
import Vuex from 'vuex';
import { serverModule } from "./modules/server";
import { userModule } from './modules/user';
import { authModule } from "./modules/auth";

Vue.use(Vuex);

const store = new Vuex.Store({
    strict: process.env.NODE_ENV !== 'production',
    modules: { userModule, serverModule, authModule },
});

if (module.hot) {
    module.hot.accept(['./modules/auth'], () => {
        const newAuthModule = require('./modules/auth').default;

        store.hotUpdate({
            modules: { newAuthModule },
        });
    });
}

export default store;
