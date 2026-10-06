import { createBrowserHistory, createRouter } from '@tanstack/react-router';
import { queryClient } from '@/api/queryClient';
import { buildRouteTree } from '@/router/routeTree';
import RouteError from '@/router/RouteError';
import Spinner from '@/components/elements/Spinner';
import { NotFound } from '@/components/elements/ScreenBlock';

export const router = createRouter({
    routeTree: buildRouteTree(),
    context: { queryClient },
    history: createBrowserHistory(),
    defaultPreload: 'intent',
    defaultPreloadDelay: 800,
    defaultPreloadStaleTime: 0,
    defaultViewTransition: true,
    scrollRestoration: true,
    defaultPendingComponent: () => <Spinner centered />,
    defaultNotFoundComponent: () => <NotFound />,
    defaultErrorComponent: RouteError,
});

declare module '@tanstack/react-router' {
    interface Register {
        router: typeof router;
    }
}

declare module '@tanstack/history' {
    interface HistoryState {
        // Login confirmation token for the 2FA checkpoint.
        token?: string;
        // Set when a request was redirected to /account for 2FA setup.
        twoFactorRedirect?: boolean;
    }
}
