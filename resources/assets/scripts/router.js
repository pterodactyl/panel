import VueRouter from 'vue-router';
import store from './store/index';
import compareDate from 'date-fns/compare_asc'
import addHours from 'date-fns/add_hours'
import dateParse from 'date-fns/parse'
const route = require('./../../../vendor/tightenco/ziggy/src/js/route').default;

// Base Vuejs Templates
import Login from './components/auth/Login';
import Dashboard from './components/dashboard/Dashboard';
import Account from './components/dashboard/Account';
import ResetPassword from './components/auth/ResetPassword';

const routes = [
    { name: 'login', path: '/auth/login', component: Login },
    { name: 'forgot-password', path: '/auth/password', component: Login },
    { name: 'checkpoint', path: '/auth/checkpoint', component: Login },
    {
        name: 'reset-password',
        path: '/auth/password/reset/:token',
        component: ResetPassword,
        props: function (route) {
            return { token: route.params.token, email: route.query.email || '' };
        }
    },

    { name : 'dashboard', path: '/', component: Dashboard },
    { name : 'account', path: '/account', component: Account },
    { name : 'account.api', path: '/account/api', component: Account },
    { name : 'account.security', path: '/account/security', component: Account },

    {
        name: 'server',
        path: '/server/:id',
        // component: Server,
        // children: [
        //     { path: 'files', component: ServerFileManager }
        // ],
    }
];

const router = new VueRouter({
    mode: 'history', routes
});

// Redirect the user to the login page if they try to access a protected route and
// have no JWT or the JWT is expired and wouldn't be accepted by the Panel.
router.beforeEach((to, from, next) => {
    if (to.path === route('auth.logout')) {
        return window.location = route('auth.logout');
    }

    const user = store.getters['auth/getUser'];

    // If user is trying to access the authentication endpoints but is already authenticated
    // don't try to load them, just send the user to the dashboard.
    if (to.path.startsWith('/auth')) {
        if (user !== null && compareDate(addHours(dateParse(user.getJWT().iat * 1000), 12), new Date()) >= 0) {
            return window.location = '/';
        }

        return next();
    }

    // If user is trying to access any of the non-authentication endpoints ensure that they have
    // a valid, non-expired JWT.
    if (!to.path.startsWith('/auth')) {
        // Check if the JWT has expired. Don't use the exp field, but rather that issued at time
        // so that we can adjust how long we want to wait for expiration on both server-side and
        // client side without having to wait for older tokens to pass their expiration time if
        // we lower it.
        if (user === null || compareDate(addHours(dateParse(user.getJWT().iat * 1000), 12), new Date()) < 0) {
            return window.location = route('auth.login');
        }
    }

    // Continue on through the pipeline.
    return next();
});

export default router;
