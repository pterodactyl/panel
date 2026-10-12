import { lazyView, type ThemeViews } from '@/sdk/theme';
import AuthLayout from './layout/AuthLayout';

const dashboard = () => import('./dashboard');

/** Classic's own views; the rest still render the existing containers as their default. */
export const classicViews: ThemeViews = {
    // The sign-in screen renders without waiting for a chunk.
    'layout.auth': AuthLayout,
    'layout.app': lazyView(() => import('./layout/AppLayout')),
    'layout.account': lazyView(() => import('./layout/AccountLayout')),
    'layout.server': lazyView(() => import('./layout/ServerLayout')),
    'layout.admin': lazyView(() => import('./layout/AdminLayout')),
    'page.dashboard': lazyView(dashboard, 'Dashboard'),
    'dashboard.serverList': lazyView(dashboard, 'ServerList'),
    'dashboard.serverCard': lazyView(dashboard, 'ServerCard'),
};
