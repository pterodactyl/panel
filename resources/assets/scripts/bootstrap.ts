import axios from './helpers/axios';

// @ts-ignore
window._ = require('lodash');

/**
 * We'll load jQuery and the Bootstrap jQuery plugin which provides support
 * for JavaScript based Bootstrap features such as modals and tabs. This
 * code may be modified to fit the specific needs of your application.
 */

try {
    // @ts-ignore
    window.$ = window.jQuery = require('jquery');
} catch (e) {}

// @ts-ignore
window.axios = axios;

/**
 * Next we will register the CSRF Token as a common header with Axios so that
 * all outgoing HTTP requests automatically have it attached. This is just
 * a simple convenience so we don't have to attach every token manually.
 */

let token = document.head.querySelector('meta[name="csrf-token"]');

if (token) {
    // @ts-ignore
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;

    // @ts-ignore
    window.X_CSRF_TOKEN = token.content;
} else {
    console.error('CSRF token not found in document.');
}
