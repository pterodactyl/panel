import Vue from 'vue';
import VueRouter from 'vue-router';
import { Ziggy } from './helpers/ziggy';

// Base Vuejs Templates
import Login from './components/auth/Login';

/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');

window.Ziggy = Ziggy;

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const route = require('./../../../../vendor/tightenco/ziggy/src/js/route').default;

Vue.config.productionTip = false;
Vue.mixin({
    methods: {
        route: route,
    },
});

Vue.use(VueRouter);

const router = new VueRouter({
    routes: [
        {
            path: '/:action?',
            component: Login,
        }
    ]
});

const app = new Vue({
    router,
}).$mount('#pterodactyl');
