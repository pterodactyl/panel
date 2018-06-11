import Vue from 'vue';
import Vuex from 'vuex';
import vuexI18n from 'vuex-i18n';
import VueRouter from 'vue-router';

require('./bootstrap');

// Helpers
import { Ziggy } from './helpers/ziggy';
import Locales from './../../../resources/lang/locales';
import { flash } from './mixins/flash';

import { routes } from './routes';
import createStore from './store';

window.events = new Vue;
window.Ziggy = Ziggy;

Vue.use(VueRouter);
const router = new VueRouter({
    mode: 'history', routes
});

Vue.use(Vuex);
const store = createStore(router);

Vue.config.productionTip = false;

const route = require('./../../../vendor/tightenco/ziggy/src/js/route').default;

Vue.mixin({ methods: { route } });
Vue.mixin(flash);

Vue.use(vuexI18n.plugin, store);

Vue.i18n.add('en', Locales.en);
Vue.i18n.set('en');

if (module.hot) {
    module.hot.accept();
}

const app = new Vue({ store, router }).$mount('#pterodactyl');
