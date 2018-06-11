import User from './../models/user';

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

let axios = require('axios');
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Authorization'] = `Bearer ${User.getToken()}`;

if (typeof phpdebugbar !== 'undefined') {
    axios.interceptors.response.use(function (response) {
        phpdebugbar.ajaxHandler.handle(response.request);

        return response;
    });
}

export default axios;
