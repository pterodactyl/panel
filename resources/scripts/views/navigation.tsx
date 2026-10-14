import { useCallback, useMemo, type ReactElement, type ReactNode } from 'react';
import { useMatchRoute } from '@tanstack/react-router';
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
import {
    PanelLink,
    resolvePanelDestination,
    type CorePanelDestination,
    type PanelDestination,
    type PanelLinkProps,
} from '@/sdk/navigation';

export type NavArea = ScreenArea;

/** An entry of an area's navigation. */
export interface NavItem {
    /** The path below the area root (`index` for the root itself); an extension item's screen id. */
    readonly id: string;
    readonly label: string;
    /** Where the item leads, for `NavigationLink`, `PanelLink`, `usePanelNavigate` and keyboard shortcuts. */
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

/** A route path, such as `/server/$id/files`, with its params as a destination; extension screens' paths resolve the same way. */
export const navItemDestination = (
    to: string,
    params: Readonly<Record<string, string>>,
    hash?: string
): PanelDestination => ({ to, params, hash }) as CorePanelDestination;

const toNavItem = (area: NavArea, entry: AreaNavEntry, order: number, params: Record<string, string>): NavItem => ({
    id: entry.screen?.id ?? (entry.segment || 'index'),
    label: entry.label,
    destination: navItemDestination(
        entry.segment ? `${SCREEN_ROOTS[area]}/${entry.segment}` : SCREEN_ROOTS[area],
        params,
        entry.hash
    ),
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
        ...tabs.map(({ id, label, to, exact }, order) => ({
            id,
            label,
            destination: navItemDestination(to, params),
            icon: null,
            group: null,
            order,
            exact: exact ?? false,
        })),
        ...screens.map(({ id, path, nav, extensionId }, index) => ({
            id,
            label: nav.label,
            destination: navItemDestination(`${SCREEN_ROOTS[parent]}/${resolveScreenPath(path, nav.params)}`, params),
            icon: nav.icon ?? null,
            group: nav.group ?? null,
            order: tabs.length + index,
            exact: nav.exact ?? false,
            extensionId,
        })),
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
                to: resolvePanelDestination(item.destination).pathname as never,
                fuzzy: !item.exact,
                includeSearch: false,
            }) !== false,
        [matchRoute]
    );
}

const pathLength = (item: NavItem): number => resolvePanelDestination(item.destination).pathname.length;

/** The most specific active item, e.g. to title a collapsed navigation. */
export function useActiveNavItem(items: readonly NavItem[]): NavItem | undefined {
    const isActive = useNavItemActive();

    return items.filter(isActive).sort((a, b) => pathLength(b) - pathLength(a))[0];
}

export interface NavigationProps {
    /** Usually a layout model's `nav.items`. */
    readonly items: readonly NavItem[];
    readonly children: NavigationRenderer;
}

/**
 * Renders each item through `children`, inside the runtime gate of its extension screen, so a
 * screen hidden by its predicate renders nothing.
 */
export function Navigation({ items, children }: NavigationProps) {
    const isActive = useNavItemActive();

    return (
        <>
            {items.map((item) => (
                <ScreenGate key={navItemKey(item)} screen={itemScreen(item)}>
                    {children(item, { active: isActive(item), badge: navItemBadge(item) })}
                </ScreenGate>
            ))}
        </>
    );
}

export interface NavigationLinkProps extends Omit<PanelLinkProps, 'destination' | 'exact' | 'includeSearch'> {
    readonly item: NavItem;
}

/** A link to an item; it carries `data-status="active"` while the item is active. */
export function NavigationLink({ item, ...props }: NavigationLinkProps): ReactElement {
    return <PanelLink destination={item.destination} exact={item.exact} {...props} />;
}
