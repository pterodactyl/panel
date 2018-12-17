import axios, {AxiosResponse} from 'axios';

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Attach the response data to phpdebugbar so that we can see everything happening.
// @ts-ignore
if (typeof phpdebugbar !== 'undefined') {
    axios.interceptors.response.use(function (response: AxiosResponse) {
        // @ts-ignore
        phpdebugbar.ajaxHandler.handle(response.request);

        return response;
    });
}

export default axios;
