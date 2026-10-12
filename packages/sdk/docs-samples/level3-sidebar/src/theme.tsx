import { defineTheme, lazyView } from '@pterodactyl/sdk/theme';

export default defineTheme({
    views: {
        'layout.app': lazyView(() => import('./views/Shell')),
        'layout.account': lazyView(() => import('./views/AccountLayout')),
    },
});
