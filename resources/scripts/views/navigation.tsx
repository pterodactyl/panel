import { useCallback, useMemo, type ComponentProps, type ReactElement, type ReactNode } from 'react';
import { Link, useMatchRoute } from '@tanstack/react-router';
import { selectServerPermissions, useServerQuery } from '@/api/server/queries';
import type { Server } from '@/api/server/types';
import {
    findExtensionScreen,
    getExtensionScreens,
    matchesScreenCondition,
    resolveScreenPath,
    SCREEN_ROOTS,
    type ScreenArea,
    type ScreenParent,
} from '@/extensions/registry';
import { ScreenBadge, ScreenGate } from '@/extensions/screenNavigation';
import { hasPermission } from '@/plugins/usePermissions';
import { getAreaNav, type AreaNavEntry } from '@/router/nav';
import { useServerRouteId } from '@/router/params';
import type { CorePanelDestination, PanelDestination } from '@/sdk/navigation';

export type NavArea = ScreenArea;

/** An entry of an area's navigation. */
export interface NavItem {
    /** The path below the area root (`index` for the root itself); an extension item's screen id. */
    readonly id: string;
    readonly label: string;
    /** The route path, e.g. `/server/$id/files`, filled in from `params`. */
    readonly to: string;
    readonly params: Readonly<Record<string, string>>;
    readonly hash?: string;
    /** Where the item leads, for `usePanelNavigate`, `PanelLink` and keyboard shortcuts. */
    readonly destination: PanelDestination;
    /** A lucide icon name, as `NamedIcon` takes it. */
    readonly icon: string | null;
    readonly group: string | null;
    /** The position in the area navigation: core items in route order, then extension screens by their `order`. */
    readonly order: number;
    /** Active only on its own path, not below it. */
    readonly exact: boolean;
    /** The extension whose screen the item opens; core items have none. */
    readonly extensionId?: string;
}

export interface NavigationModel {
    readonly items: readonly NavItem[];
}

export interface NavItemState {
    readonly active: boolean;
    /** An extension screen's live badge, its manifest badge until that answers, or null. It suspends locally. */
    readonly badge: ReactNode;
}

export type NavigationRenderer = (item: NavItem, state: NavItemState) => ReactNode;

/** A key that is unique within one navigation. */
export const navItemKey = (item: NavItem): string => `${item.extensionId ?? ''}:${item.id}`;

/** An item's route path and params as a destination; extension screens' paths resolve the same way. */
export const navItemDestination = ({ to, params, hash }: Pick<NavItem, 'to' | 'params' | 'hash'>): PanelDestination =>
    ({ to, params, hash }) as CorePanelDestination;

const withDestination = (item: Omit<NavItem, 'destination'>): NavItem => ({
    ...item,
    destination: navItemDestination(item),
});

const toNavItem = (area: NavArea, entry: AreaNavEntry, order: number, params: Record<string, string>): NavItem =>
    withDestination({
        id: entry.screen?.id ?? (entry.segment || 'index'),
        label: entry.label,
        to: entry.segment ? `${SCREEN_ROOTS[area]}/${entry.segment}` : SCREEN_ROOTS[area],
        params,
        hash: entry.hash,
        icon: entry.icon ?? null,
        group: entry.group ?? null,
        order,
        exact: entry.exact ?? false,
        extensionId: entry.screen?.extensionId,
    });

/** Server items need one of their subuser permissions and, for extension screens, a matching egg. */
function isListed(entry: AreaNavEntry, server: Server | undefined): boolean {
    const permissions = server ? selectServerPermissions(server) : [];
    const required = entry.permission === undefined ? [] : [entry.permission].flat();

    return (
        (!required.length || required.some((permission) => hasPermission(permissions, permission))) &&
        matchesScreenCondition(entry.screen?.when, server)
    );
}

/**
 * The navigation of an area, filtered by subuser permissions and egg rules. Screens with a runtime
 * predicate stay listed; `<Navigation>` renders them once the predicate allows it.
 */
export function useNavigation(area: NavArea): NavigationModel {
    const id = useServerRouteId();
    const serverId = area === 'server' ? (id ?? '') : '';
    const server = useServerQuery(serverId).data;

    return useMemo(() => {
        const params: Record<string, string> = serverId ? { id: serverId } : {};
        const items = getAreaNav(area)
            .map((entry, order) => ({ entry, item: toNavItem(area, entry, order, params) }))
            .filter(({ entry }) => area !== 'server' || isListed(entry, server))
            .map(({ item }) => item);

        return { items };
    }, [area, serverId, server]);
}

export interface ResourceTab {
    readonly id: string;
    readonly label: string;
    readonly to: string;
    readonly exact?: boolean;
}

/** The tabs of an admin resource: its own, then the screens extensions add beneath it. */
export function resourceNavigation(
    parent: ScreenParent,
    params: Readonly<Record<string, string>>,
    tabs: readonly ResourceTab[]
): NavItem[] {
    const screens = getExtensionScreens('admin', parent).flatMap(({ id, path, nav, extensionId }) =>
        nav ? [{ id, path, nav, extensionId }] : []
    );

    return [
        ...tabs.map((tab, order) =>
            withDestination({ ...tab, params, icon: null, group: null, order, exact: tab.exact ?? false })
        ),
        ...screens.map(({ id, path, nav, extensionId }, index) =>
            withDestination({
                id,
                label: nav.label,
                to: `${SCREEN_ROOTS[parent]}/${resolveScreenPath(path, nav.params)}`,
                params,
                icon: nav.icon ?? null,
                group: nav.group ?? null,
                order: tabs.length + index,
                exact: nav.exact ?? false,
                extensionId,
            })
        ),
    ];
}

const itemScreen = (item: NavItem) =>
    item.extensionId === undefined ? undefined : findExtensionScreen(item.extensionId, item.id);

export function navItemBadge(item: NavItem): ReactNode {
    const screen = itemScreen(item);

    return screen ? <ScreenBadge screen={screen} badge={screen.nav?.badge} /> : null;
}

/** Whether an item is active: on its path, or below it unless it is exact. */
export function useNavItemActive(): (item: NavItem) => boolean {
    const matchRoute = useMatchRoute();

    return useCallback(
        (item: NavItem) =>
            matchRoute({
                to: item.to as never,
                params: item.params as never,
                fuzzy: !item.exact,
                includeSearch: false,
            }) !== false,
        [matchRoute]
    );
}

/** The most specific active item, e.g. to title a collapsed navigation. */
export function useActiveNavItem(items: readonly NavItem[]): NavItem | undefined {
    const isActive = useNavItemActive();

    return items.filter(isActive).sort((a, b) => b.to.length - a.to.length)[0];
}

function NavigationItems({ items, render }: { items: readonly NavItem[]; render: NavigationRenderer }) {
    const isActive = useNavItemActive();

    return items.map((item) => (
        <ScreenGate key={navItemKey(item)} screen={itemScreen(item)}>
            {render(item, { active: isActive(item), badge: navItemBadge(item) })}
        </ScreenGate>
    ));
}

function AreaNavigation({ area, render }: { area: NavArea; render: NavigationRenderer }) {
    return <NavigationItems items={useNavigation(area).items} render={render} />;
}

export type NavigationProps = { readonly children: NavigationRenderer } & (
    | { readonly area: NavArea; readonly items?: undefined }
    | { readonly items: readonly NavItem[]; readonly area?: undefined }
);

/**
 * Renders each item through `children`, inside the runtime gate of its extension screen, so a
 * screen hidden by its predicate renders nothing. `items` (usually a layout model's) take the
 * place of the area's live navigation.
 */
export function Navigation(props: NavigationProps) {
    return props.items ? (
        <NavigationItems items={props.items} render={props.children} />
    ) : (
        <AreaNavigation area={props.area} render={props.children} />
    );
}

export interface NavigationLinkProps extends Omit<ComponentProps<'a'>, 'href'> {
    readonly item: NavItem;
}

/** A link to an item; it carries `data-status="active"` while the item is active. */
export function NavigationLink({ item, ...props }: NavigationLinkProps): ReactElement {
    return (
        <Link
            to={item.to as never}
            params={item.params as never}
            hash={item.hash}
            activeOptions={{ exact: item.exact, includeSearch: false }}
            {...props}
        />
    );
}
