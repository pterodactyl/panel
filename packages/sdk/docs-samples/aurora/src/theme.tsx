import { Boxes } from 'lucide-react';
import { defineTheme, lazyView } from '@pterodactyl/sdk/theme';
import { auroraSkins } from './skins';
import AuthLayout from './views/AuthLayout';

export default defineTheme({
    ui: auroraSkins,
    views: {
        // The sign-in screen renders without waiting for a chunk.
        'layout.auth': AuthLayout,
        'page.auth.login': lazyView(() => import('./views/LoginPage')),
        'layout.app': lazyView(() => import('./views/AppShell')),
        'layout.server': lazyView(() => import('./views/ServerLayout')),
        'server.power': lazyView(() => import('./views/Power')),
        'page.server.console': lazyView(() => import('./views/ConsolePage')),
        'server.terminal': lazyView(() => import('./views/Terminal')),
        'server.stats': lazyView(() => import('./views/Stats')),
        'dashboard.serverList': lazyView(() => import('./views/ServerGrid')),
        'dashboard.serverCard': lazyView(() => import('./views/ServerCard')),
    },
    // Every <NamedIcon name="server"/> draws this in place of lucide's, such as a navigation item naming it.
    icons: { server: Boxes },
});
