import Vue from 'vue';
import Vuex from 'vuex';
import vuexI18n from 'vuex-i18n';
import VueRouter from 'vue-router';

// Helpers
import { Ziggy } from './helpers/ziggy';
import Locales from './../../../resources/lang/locales';
import { flash } from './mixins/flash';

import fontawesome from '@fortawesome/fontawesome';
import faSolid from '@fortawesome/fontawesome-free-solid';
import FontAwesomeIcon from '@fortawesome/vue-fontawesome';
fontawesome.library.add(faSolid);

// Base Vuejs Templates
import Login from './components/auth/Login';
import Dashboard from './components/dashboard/Dashboard';
import Account from './components/dashboard/Account';
import ResetPassword from './components/auth/ResetPassword';
import { Server, ServerConsole, ServerAllocations, ServerDatabases, ServerFiles, ServerSchedules, ServerSettings, ServerSubusers } from './components/server';

window.events = new Vue;
window.Ziggy = Ziggy;

Vue.use(Vuex);

const store = new Vuex.Store();
const route = require('./../../../vendor/tightenco/ziggy/src/js/route').default;

Vue.config.productionTip = false;
Vue.mixin({ methods: { route } });
Vue.mixin(flash);

Vue.use(VueRouter);
Vue.use(vuexI18n.plugin, store);

Vue.i18n.add('en', Locales.en);
Vue.i18n.set('en');

Vue.component('font-awesome-icon', FontAwesomeIcon);

const router = new VueRouter({
    mode: 'history',
    routes: [
        { name: 'login', path: '/auth/login', component: Login },
        { name: 'forgot-password', path: '/auth/password', component: Login },
        { name: 'checkpoint', path: '/checkpoint', component: Login },
        {
            name: 'reset-password',
            path: '/auth/password/reset/:token',
            component: ResetPassword,
            props: function (route) {
                return { token: route.params.token, email: route.query.email || '' };
            }
        },
        { name : 'index', path: '/', component: Dashboard },
        { name : 'account', path: '/account', component: Account },
        { name : 'account-api', path: '/account/api', component: Account },
        { name : 'account-security', path: '/account/security', component: Account },
        { path: '/server/:id', component: Server,
            children: [
                { name: 'server', path: '', component: ServerConsole },
                { name: 'server-files', path: 'files', component: ServerFiles },
                { name: 'server-subusers', path: 'subusers', component: ServerSubusers },
                { name: 'server-schedules', path: 'schedules', component: ServerSchedules },
                { name: 'server-databases', path: 'databases', component: ServerDatabases },
                { name: 'server-allocations', path: 'allocations', component: ServerAllocations },
                { name: 'server-settings', path: 'settings', component: ServerSettings },
            ]
        }
    ]
});

require('./bootstrap');

const app = new Vue({ store, router }).$mount('#pterodactyl');
