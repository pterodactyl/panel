import { lazyView, type ThemeViews } from '@/sdk/theme';

const layout = () => import('./layout/AppLayout');
const dashboard = () => import('./dashboard');

/** Classic's own views; the rest still render the existing containers as their default. */
export const classicViews: ThemeViews = {
    'layout.app': lazyView(layout),
    'page.dashboard': lazyView(dashboard, 'Dashboard'),
    'dashboard.serverList': lazyView(dashboard, 'ServerList'),
    'dashboard.serverCard': lazyView(dashboard, 'ServerCard'),
};
