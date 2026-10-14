import { defineTheme, lazyView } from '@pterodactyl/sdk/theme';

export default defineTheme({
    views: {
        // Each view loads its chunk the first time it renders; the router preloads it with the route.
        'layout.server': lazyView(() => import('./views/ServerLayout')),
        'page.server.console': lazyView(() => import('./views/ConsolePage')),
    },
});
