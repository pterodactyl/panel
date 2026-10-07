import { useCallback, type ComponentProps, type ReactElement } from 'react';
import { Link, useNavigate, useLocation, useParams, useSearch } from '@tanstack/react-router';
import { findExtensionScreen, resolveScreenPath, SCREEN_ROOTS, type RouteSlotData } from '@/extensions/registry';
import { routeParamStrings } from '@/router/params';
import { parseRedirectSearch } from '@/router/redirect';
import type { ExtensionConfigValue } from './index';

export {
    useNavigationBlocker,
    type NavigationBlockerLocation,
    type NavigationBlockerOptions,
    type NavigationTransition,
} from '@/plugins/useNavigationBlocker';

export type CorePanelPath =
    | '/'
    | `/account${'' | '/api' | '/ssh' | '/activity'}`
    | `/server/$id${'' | '/files' | '/databases' | '/schedules' | '/users' | '/users/new' | '/backups' | '/network' | '/startup' | '/settings' | '/activity'}`
    | '/server/$id/files/$action'
    | '/server/$id/schedules/$scheduleId'
    | `/panel${'' | '/users' | '/locations' | '/nodes' | '/servers' | '/databases' | '/mounts' | '/eggs' | '/tags' | '/extensions' | '/api' | '/activity' | '/settings' | '/settings/mail' | '/settings/advanced'}`
    | `/panel/${'users' | 'locations' | 'nodes' | 'servers' | 'databases' | 'mounts'}/$id`
    | `/panel/${'users' | 'nodes' | 'servers' | 'databases' | 'mounts' | 'eggs'}/new`
    | `/panel/nodes/$id/${'settings' | 'configuration' | 'allocation' | 'servers'}`
    | `/panel/servers/$id/${'details' | 'build' | 'startup' | 'database' | 'mounts' | 'manage' | 'delete'}`
    | `/panel/eggs/$eggId${'' | '/tags' | '/variables' | '/script'}`
    | '/panel/eggs/catalog';
type PathParameters<TPath extends string> = TPath extends `${string}$${infer TParameter}/${infer TRest}`
    ? TParameter | PathParameters<TRest>
    : TPath extends `${string}$${infer TParameter}`
      ? TParameter
      : never;
export type PanelSearch = Record<string, ExtensionConfigValue | undefined>;
export type CorePanelDestination = {
    [TPath in CorePanelPath]: { to: TPath; search?: PanelSearch; hash?: string; replace?: boolean } & ([
        PathParameters<TPath>,
    ] extends [never]
        ? { params?: never }
        : { params: Record<PathParameters<TPath>, string> });
}[CorePanelPath];
export interface ExtensionPanelDestination {
    extension: string;
    screen: string;
    params?: Record<string, string>;
    search?: PanelSearch;
    hash?: string;
    replace?: boolean;
}
export type PanelDestination = CorePanelDestination | ExtensionPanelDestination;

export interface ResolvedPanelDestination {
    pathname: string;
    search?: PanelSearch;
    hash?: string;
    replace?: boolean;
}
export function resolvePanelDestination(destination: PanelDestination): ResolvedPanelDestination {
    let path: string;
    if ('to' in destination) {
        path = destination.to;
    } else {
        const screen = findExtensionScreen(destination.extension, destination.screen);
        if (!screen) throw new Error(`Unknown extension screen "${destination.extension}:${destination.screen}".`);
        path = `${SCREEN_ROOTS[screen.parent ?? screen.area]}/${screen.path}`;
    }
    const pathname = resolveScreenPath(path, destination.params);
    return { pathname, search: destination.search, hash: destination.hash, replace: destination.replace };
}

export function usePanelNavigate(): (destination: PanelDestination) => Promise<void> {
    const navigate = useNavigate();
    return useCallback(
        (destination: PanelDestination) => {
            const { pathname, ...options } = resolvePanelDestination(destination);
            return navigate({ to: pathname as never, ...options, search: options.search as never });
        },
        [navigate]
    );
}
export function usePanelLocation(): RouteSlotData {
    const { pathname } = useLocation();
    const params = useParams({ strict: false });
    const search = useSearch({ strict: false });
    return { pathname, params: routeParamStrings(params), search };
}
export interface PanelLinkProps extends Omit<ComponentProps<'a'>, 'href'> {
    destination: PanelDestination;
    exact?: boolean;
}
export function PanelLink({ destination, exact = false, ...props }: PanelLinkProps): ReactElement {
    const { pathname, ...options } = resolvePanelDestination(destination);
    return (
        <Link
            to={pathname as never}
            {...options}
            search={options.search as never}
            activeOptions={{ exact, includeSearch: false }}
            {...props}
        />
    );
}

export function useOpenLoginCheckpoint(): (confirmationToken: string) => Promise<void> {
    const navigate = useNavigate();
    const { redirect } = parseRedirectSearch(useSearch({ strict: false }));
    return useCallback(
        (confirmationToken: string) =>
            navigate({
                to: '/auth/login/checkpoint',
                search: { redirect },
                replace: true,
                state: { token: confirmationToken } as never,
            }),
        [navigate, redirect]
    );
}
