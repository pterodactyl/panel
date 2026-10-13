import type { ComponentType, FunctionComponent, ReactNode } from 'react';
import { lazyRouteComponent, Outlet } from '@tanstack/react-router';
import Spinner from '@/components/elements/Spinner';
import PermissionRoute from '@/components/elements/PermissionRoute';
import Slot from '@/extensions/Slot';
import { hasPermission } from '@/plugins/usePermissions';
import { RouteAccessDenied } from '@/router/RouteError';
import { useRouteSlotData } from '@/router/routeSlots';
import { pageSlots, type PageSlots, type ViewPageName } from '@/views/pages';
import { ViewSessionProvider } from '@/views/session';
import { preloadView, View } from '@/views/View';

export type LayoutViewName = 'layout.auth' | 'layout.app' | 'layout.account' | 'layout.server' | 'layout.admin';

/** A server tab `beforeLoad` matching PermissionRoute's any-of check. */
export const requireServerPermission =
    (permission: string | string[]) =>
    ({ context }: { context: { serverPermissions: readonly string[] } }): void => {
        const required = Array.isArray(permission) ? permission : [permission];

        if (!required.some((name) => hasPermission(context.serverPermissions, name))) {
            throw new RouteAccessDenied();
        }
    };

export function layoutRoute(name: LayoutViewName) {
    function LayoutRoute() {
        return (
            <View name={name}>
                <Outlet />
            </View>
        );
    }

    LayoutRoute.preload = () => preloadView(name);

    return LayoutRoute;
}

/** A page's permission guard, then the page between the slots rendered before and after it. */
function PageFrame({
    permission,
    slots,
    children,
}: {
    permission: string | string[] | null;
    slots: PageSlots | undefined;
    children: ReactNode;
}) {
    const data = useRouteSlotData();

    return (
        <PermissionRoute permission={permission}>
            <ViewSessionProvider>
                {slots && <Slot name={slots[0]} data={data} />}
                <Spinner.Suspense>{children}</Spinner.Suspense>
                {slots && <Slot name={slots[1]} data={data} />}
            </ViewSessionProvider>
        </PermissionRoute>
    );
}

/**
 * The route component of a page view, between the slots the engine renders around it, so a theme
 * cannot drop them. `Frame` is engine state the page's views share, such as an editing session,
 * rendered around the page view whatever the theme draws.
 */
export function pageRoute(
    name: Exclude<ViewPageName, 'page.server.console'>,
    permission: string | string[] | null = null,
    Frame?: ComponentType<{ children?: ReactNode }> & { preload?: () => Promise<unknown> }
) {
    const slots = pageSlots(name);

    function PageRoute() {
        const page = <View name={name} />;

        return (
            <PageFrame permission={permission} slots={slots}>
                {Frame ? <Frame>{page}</Frame> : page}
            </PageFrame>
        );
    }

    PageRoute.preload = async () => {
        await Promise.all([preloadView(name), Frame?.preload?.()]);
    };

    return PageRoute;
}

/** The route component of a page that is a plain component, such as an admin page, between its slots. */
export function componentRoute(importer: () => Promise<{ default: FunctionComponent }>, slots?: PageSlots) {
    const Page = lazyRouteComponent(importer);

    function ComponentRoute() {
        return (
            <PageFrame permission={null} slots={slots}>
                <Page />
            </PageFrame>
        );
    }

    ComponentRoute.preload = Page.preload;

    return ComponentRoute;
}
