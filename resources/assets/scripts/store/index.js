import Vuex from 'vuex';
import { sync } from 'vuex-router-sync';
import { serverModule } from "./modules/server";
import { userModule } from './modules/user'

const createStore = (router) => {
    const store = new Vuex.Store({
        //strict: process.env.NODE_ENV !== 'production',
        modules: {
            userModule,
            serverModule,
        },
    });
    sync(store, router);
    return store;
};

export default createStore;
