import VueRouter, {Route} from 'vue-router';
import store from './store/index';
import User from './models/user';

const route = require('./../../../vendor/tightenco/ziggy/src/js/route').default;

// Base Vuejs Templates
import Login from './components/auth/Login.vue';
import Dashboard from './components/dashboard/Dashboard.vue';
import Account from './components/dashboard/Account.vue';
import ResetPassword from './components/auth/ResetPassword.vue';
import LoginForm from "@/components/auth/LoginForm.vue";
import ForgotPassword from "@/components/auth/ForgotPassword.vue";
import TwoFactorForm from "@/components/auth/TwoFactorForm.vue";
import Server from "@/components/server/Server.vue";
import ConsolePage from "@/components/server/subpages/Console.vue";
import FileManagerPage from "@/components/server/subpages/FileManager.vue";
import DatabasesPage from "@/components/server/subpages/Databases.vue";

const routes = [
    {
        path: '/auth', component: Login,
        children: [
            { name: 'login', path: 'login', component: LoginForm },
            { name: 'forgot-password', path: 'password', component: ForgotPassword },
            { name: 'checkpoint', path: 'checkpoint', component: TwoFactorForm },
        ]
    },

    {
        name: 'reset-password',
        path: '/auth/password/reset/:token',
        component: ResetPassword,
        props: function (route: Route) {
            return {token: route.params.token, email: route.query.email || ''};
        },
    },

    {name: 'dashboard', path: '/', component: Dashboard},
    {name: 'account', path: '/account', component: Account},
    {name: 'account.api', path: '/account/api', component: Account},
    {name: 'account.security', path: '/account/security', component: Account},

    {
        path: '/server/:id', component: Server,
        children: [
            {name: 'server', path: '', component: ConsolePage},
            {name: 'server-files', path: 'files/:path(.*)?', component: FileManagerPage},
            // {name: 'server-subusers', path: 'subusers', component: ServerSubusers},
            // {name: 'server-schedules', path: 'schedules', component: ServerSchedules},
            {name: 'server-databases', path: 'databases', component: DatabasesPage},
            // {name: 'server-allocations', path: 'allocations', component: ServerAllocations},
            // {name: 'server-settings', path: 'settings', component: ServerSettings},
        ],
    },
];

const router = new VueRouter({
    mode: 'history', routes,
});

// Redirect the user to the login page if they try to access a protected route and
// have no JWT or the JWT is expired and wouldn't be accepted by the Panel.
router.beforeEach((to, from, next) => {
    if (to.path === route('auth.logout')) {
        return window.location = route('auth.logout');
    }

    const user = store.getters['auth/getUser'];

    // Check that if we're accessing a non-auth route that a user exists on the page.
    if (!to.path.startsWith('/auth') && !(user instanceof User)) {
        store.commit('auth/logout');
        return window.location = route('auth.logout');
    }

    // Continue on through the pipeline.
    return next();
});

export default router;
