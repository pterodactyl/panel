import Vue from 'vue';
import Vuex from 'vuex';
import vuexI18n from 'vuex-i18n';
import VueRouter from 'vue-router';
import VeeValidate from 'vee-validate';

Vue.config.productionTip = false;
require('./bootstrap');

// Helpers
import { Ziggy } from './helpers/ziggy';
import Locales from './../../../resources/lang/locales';
import { flash } from './mixins/flash';
import store from './store/index.js';
import router from './router';

window.events = new Vue;
window.Ziggy = Ziggy;

Vue.use(Vuex);
Vue.use(VueRouter);
Vue.use(vuexI18n.plugin, store);
Vue.use(VeeValidate);

const route = require('./../../../vendor/tightenco/ziggy/src/js/route').default;

Vue.mixin({ methods: { route } });
Vue.mixin(flash);

Vue.i18n.add('en', Locales.en);
Vue.i18n.set('en');

if (module.hot) {
    module.hot.accept();
}

const app = new Vue({ store, router }).$mount('#pterodactyl');
