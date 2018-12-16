import axios from 'axios';

// This token is set in the bootstrap.js file at the beginning of the request
// and is carried through from there.
// const token: string = '';

const http = axios.create({
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
    'Content-Type': 'application/json',
});

// If we have a phpdebugbar instance registered at this point in time go
// ahead and route the response data through to it so things show up.
if (typeof window.phpdebugbar !== 'undefined') {
    http.interceptors.response.use(response => {
        window.phpdebugbar.ajaxHandler.handle(response.request);

        return response;
    });
}

export default http;
