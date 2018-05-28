// Base Vuejs Templates
import Login from './components/auth/Login';
import Dashboard from './components/dashboard/Dashboard';
import Account from './components/dashboard/Account';
import ResetPassword from './components/auth/ResetPassword';
import { Server, ServerConsole, ServerAllocations, ServerDatabases, ServerFiles, ServerSchedules, ServerSettings, ServerSubusers } from './components/server';

import {
    Server, ServerAllocations, ServerConsole,
    ServerDatabases,
    ServerFiles,
    ServerSchedules,
    ServerSettings,
    ServerSubusers
} from "./components/server";

export const routes = [
    { name: 'login', path: '/auth/login', component: Login },
    { name: 'forgot-password', path: '/auth/password', component: Login },
    { name: 'checkpoint', path: '/checkpoint', component: Login },
    {
        name: 'reset-password',
        path: '/auth/password/reset/:token',
        component: ResetPassword,
        props: function (route) {
            return { token: route.params.token, email: route.query.email || '' };
        }
    },

    { name : 'index', path: '/', component: Dashboard },
    { name : 'account', path: '/account', component: Account },
    { name : 'account.api', path: '/account/api', component: Account },
    { name : 'account.security', path: '/account/security', component: Account },

    { path: '/server/:id', component: Server,
        children: [
            { name: 'server', path: '', component: ServerConsole },
            { name: 'server-files', path: 'files', component: ServerFiles },
            { name: 'server-subusers', path: 'subusers', component: ServerSubusers },
            { name: 'server-schedules', path: 'schedules', component: ServerSchedules },
            { name: 'server-databases', path: 'databases', component: ServerDatabases },
            { name: 'server-allocations', path: 'allocations', component: ServerAllocations },
            { name: 'server-settings', path: 'settings', component: ServerSettings },
        ]
    }
];
