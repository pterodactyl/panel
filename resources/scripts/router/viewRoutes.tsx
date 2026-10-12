import type { ComponentType, ReactNode } from 'react';
import { Outlet } from '@tanstack/react-router';
import Spinner from '@/components/elements/Spinner';
import PermissionRoute from '@/components/elements/PermissionRoute';
import Slot from '@/extensions/Slot';
import { hasPermission } from '@/plugins/usePermissions';
import { RouteAccessDenied } from '@/router/RouteError';
import { useRouteSlotData } from '@/router/routeSlots';
import { LayoutMainCheck } from '@/views/a11y';
import { pageSlots, type PageViewName } from '@/views/pages';
import { ViewSessionProvider } from '@/views/session';
import { EngineSlots } from '@/views/slotCheck';
import { preloadView, View, ViewArea } from '@/views/View';
import type { ViewContext } from '@/views/registry';

const noSlots: readonly string[] = [];

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

/** Renders a layout view in its area with the routed content as its children. */
export function LayoutView({ name, area, children }: { name: LayoutViewName; area: ViewContext; children: ReactNode }) {
    return (
        <ViewArea area={area}>
            <LayoutMainCheck name={name}>
                <View name={name}>{children}</View>
            </LayoutMainCheck>
        </ViewArea>
    );
}

export function layoutRoute(name: LayoutViewName, area: ViewContext) {
    function LayoutRoute() {
        return (
            <LayoutView name={name} area={area}>
                <Outlet />
            </LayoutView>
        );
    }

    LayoutRoute.preload = () => preloadView(name);

    return LayoutRoute;
}

/**
 * The route component of a page: its permission guard, then the page view between the slots the
 * engine renders before and after it, so a theme cannot drop them. `Frame` is engine state the page's
 * views share, such as an editing session, rendered around the page view whatever the theme draws.
 */
export function pageRoute(
    name: Exclude<PageViewName, 'page.server.console'>,
    permission: string | string[] | null = null,
    Frame?: ComponentType<{ children?: ReactNode }>
) {
    const slots = pageSlots(name);

    function PageRoute() {
        const data = useRouteSlotData();
        const page = <View name={name} />;

        return (
            <PermissionRoute permission={permission}>
                <ViewSessionProvider>
                    {slots && <Slot name={slots[0]} data={data} />}
                    <EngineSlots.Provider value={slots ?? noSlots}>
                        <Spinner.Suspense>{Frame ? <Frame>{page}</Frame> : page}</Spinner.Suspense>
                    </EngineSlots.Provider>
                    {slots && <Slot name={slots[1]} data={data} />}
                </ViewSessionProvider>
            </PermissionRoute>
        );
    }

    PageRoute.preload = () => preloadView(name);

    return PageRoute;
}

/** A page of an area rendered outside that area's layout, as the full-width theme workbench is. */
export function bareAreaPageRoute(name: Exclude<PageViewName, 'page.server.console'>, area: ViewContext) {
    const Page = pageRoute(name);

    function BarePageRoute() {
        return (
            <ViewArea area={area}>
                <Page />
            </ViewArea>
        );
    }

    BarePageRoute.preload = Page.preload;

    return BarePageRoute;
}
