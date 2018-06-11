import Vuex from 'vuex';
import { sync } from 'vuex-router-sync';
import { serverModule } from "./modules/server";
import { userModule } from './modules/user';
import { authModule } from "./modules/auth";

const createStore = (router) => {
    const store = new Vuex.Store({
        strict: process.env.NODE_ENV !== 'production',
        modules: {
            userModule,
            serverModule,
            authModule,
        },
    });
    sync(store, router);
    return store;
};

export default createStore;
