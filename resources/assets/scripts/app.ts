import Vue from 'vue';
import Vuex from 'vuex';
import VueI18n from 'vue-i18n';
import VueRouter from 'vue-router';
import VeeValidate from 'vee-validate';
// Helpers
// @ts-ignore
import {Ziggy} from './helpers/ziggy';
// @ts-ignore
import Locales from './../../../resources/lang/locales';

import {flash} from './mixins/flash';
import store from './store/index';
import router from './router';

Vue.config.productionTip = false;
require('./bootstrap');

// @ts-ignore
window.events = new Vue();
// @ts-ignore
window.Ziggy = Ziggy;

Vue.use(Vuex);
Vue.use(VueRouter);
Vue.use(VeeValidate);
Vue.use(VueI18n);

// $FlowFixMe: this is always going to be unhappy because we ignore the vendor dir.
const route = require('./../../../vendor/tightenco/ziggy/src/js/route').default;

Vue.mixin({methods: {route}});
Vue.mixin(flash);

const i18n = new VueI18n({
    locale: 'en',
    messages: {...Locales},
});

// $FlowFixMe
if (module.hot) {
    module.hot.accept();
}

new Vue({store, router, i18n}).$mount('#pterodactyl');
