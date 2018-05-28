import Vue from 'vue';
import Vuex from 'vuex';
import vuexI18n from 'vuex-i18n';
import VueRouter from 'vue-router';

// Helpers
import { Ziggy } from './helpers/ziggy';
import Locales from './../../../resources/lang/locales';
import { flash } from './mixins/flash';
import { routes } from './routes';

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

const router = new VueRouter({
    mode: 'history', routes
});

require('./bootstrap');

const app = new Vue({ store, router }).$mount('#pterodactyl');
