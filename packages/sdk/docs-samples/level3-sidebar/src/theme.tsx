import type { ReactNode } from 'react';
import { Main, defineTheme, lazyView } from '@pterodactyl/sdk/theme';

/** The shell draws every area's navigation, so an area's layout only places its pages. */
function AreaLayout({ children }: { children?: ReactNode }) {
    return <Main>{children}</Main>;
}

export default defineTheme({
    views: {
        'layout.app': lazyView(() => import('./views/Shell')),
        'layout.server': AreaLayout,
        'layout.account': AreaLayout,
        'layout.admin': AreaLayout,
    },
});
