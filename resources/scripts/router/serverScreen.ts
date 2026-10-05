import { createElement, Fragment, type FunctionComponent } from 'react';
import { lazyRouteComponent } from '@tanstack/react-router';
import Spinner from '@/components/elements/Spinner';
import PermissionRoute from '@/components/elements/PermissionRoute';
import Slot from '@/extensions/Slot';
import { hasPermission } from '@/plugins/usePermissions';
import { RouteAccessDenied } from '@/router/RouteError';
import { type RouteSlotPair, useRouteSlotData } from '@/router/routeSlots';

type Importer = () => Promise<{ default: FunctionComponent }>;

/** A server tab `beforeLoad` matching PermissionRoute's any-of check. */
export const requireServerPermission =
    (permission: string | string[]) =>
    ({ context }: { context: { serverPermissions: readonly string[] } }): void => {
        const required = Array.isArray(permission) ? permission : [permission];

        if (!required.some((name) => hasPermission(context.serverPermissions, name))) {
            throw new RouteAccessDenied();
        }
    };

/** A `null` permission renders the screen unguarded. */
export const serverScreen = (factory: Importer, permission: string | string[] | null, slots?: RouteSlotPair) => {
    const Lazy = lazyRouteComponent(factory);

    function ServerScreen() {
        const data = useRouteSlotData();

        return createElement(
            PermissionRoute,
            { permission },
            createElement(
                Fragment,
                null,
                slots ? createElement(Slot, { name: slots.before, data }) : null,
                createElement(Spinner.Suspense, null, createElement(Lazy)),
                slots ? createElement(Slot, { name: slots.after, data }) : null
            )
        );
    }

    ServerScreen.preload = Lazy.preload;

    return ServerScreen;
};
