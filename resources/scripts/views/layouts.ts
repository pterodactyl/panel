import { useMemo } from 'react';
import { useQuery } from '@tanstack/react-query';
import { useRouterState } from '@tanstack/react-router';
import { useCurrentUser } from '@/api/account/queries';
import { serverResourceUsageQueryOptions, type ServerPowerState } from '@/api/account/servers/queries';
import { useLogout } from '@/api/auth/queries';
import { useCurrentServer } from '@/api/server/queries';
import type { Server } from '@/api/server/types';
import { useSiteSettings } from '@/api/settings/queries';
import { SCREEN_ROOTS } from '@/extensions/registry';
import { isInstallingStatus } from '@/lib/serverStatus';
import type { CorePanelDestination } from '@/sdk/navigation';
import { useBrandLogo } from './brand';
import { createTestServer } from './fixtures';
import { useNavigation, type NavArea, type NavigationModel, type NavItem } from './navigation';
import type { OpaqueModel } from './pages';
import { implementViews } from './registry';
import type { ServerRunState } from './server/console';
import { useTheme, type ThemeScheme } from './theme';

export interface LayoutBrand {
    /** The panel's name. */
    readonly name: string;
    /** The image the active theme's setting mapped to `--brand-logo` holds, if it has one. */
    readonly logoUrl: string | null;
}

export interface AuthLayoutModel {
    readonly brand: LayoutBrand;
}

export interface AppLayoutLink {
    readonly id: 'dashboard' | 'admin' | 'account';
    readonly label: string;
    readonly destination: CorePanelDestination;
    /** Active only on the destination itself, not below it. */
    readonly exact: boolean;
    /** A lucide icon name, as `NamedIcon` takes it. */
    readonly icon: string;
}
export interface AppLayoutModel {
    readonly brand: LayoutBrand;
    /** The dashboard, the admin area for root admins, and the account. */
    readonly links: readonly AppLayoutLink[];
    /** Where the user is, with the model of that area's layout, so the shell can draw its navigation. */
    readonly area: AppArea;
    readonly user: {
        readonly uuid: string;
        readonly username: string;
        readonly email: string;
        readonly rootAdmin: boolean;
    };
    readonly scheme: ThemeScheme;
    /** The schemes the theme offers; the first is its default. */
    readonly schemes: readonly ThemeScheme[];
    /** Switches the scheme without a reload; null unless the theme offers more than one. */
    readonly setScheme: ((preference: ThemeScheme | 'system') => void) | null;
    readonly signOut: () => void;
    readonly signingOut: boolean;
}

export interface AccountLayoutModel {
    readonly nav: NavigationModel;
}

/** Why a server's pages show a notice instead of their content. */
export type ServerConflict = 'installing' | 'suspended' | 'maintenance' | 'transferring' | 'restoring-backup';

/**
 * The server area's frame. Its `children` already hold the server's console, which stays mounted
 * while another page shows, and swap the pages for the conflict notice when `conflict` is set.
 */
export interface ServerLayoutModel {
    readonly name: string;
    readonly identifier: string;
    readonly uuid: string;
    /** The server's id in the admin area. */
    readonly internalId: number;
    /** What Wings last reported the server doing, as `server.power` has it; null until it has. */
    readonly state: ServerRunState | null;
    readonly conflict: ServerConflict | null;
    /** The server's admin page, for root admins. */
    readonly adminLink: NavItem | null;
    readonly nav: NavigationModel;
    /** The server as the API returns it, which the navigation slots receive. */
    readonly server: Server;
}

export interface AdminLayoutModel {
    readonly nav: NavigationModel;
    readonly user: {
        readonly uuid: string;
        readonly username: string;
        readonly email: string;
    };
}

/**
 * The area `layout.app` renders: the dashboard (and any page outside the areas below), or an area with
 * the model of its own layout, which `layout.app` renders around the area's pages.
 */
export type AppArea =
    | { readonly kind: 'dashboard' }
    | ({ readonly kind: 'account' } & AccountLayoutModel)
    | ({ readonly kind: 'server' } & ServerLayoutModel)
    | ({ readonly kind: 'admin' } & AdminLayoutModel);

declare module './registry' {
    interface ViewModels {
        'layout.auth': AuthLayoutModel;
        'layout.app': AppLayoutModel;
        'layout.account': AccountLayoutModel;
        'layout.server': ServerLayoutModel;
        'layout.admin': AdminLayoutModel;
        'app.search': OpaqueModel;
    }
}

function useBrand(): LayoutBrand {
    const { name } = useSiteSettings();
    const logoUrl = useBrandLogo();

    return useMemo(() => ({ name, logoUrl }), [name, logoUrl]);
}

function useAuthLayoutModel(): AuthLayoutModel {
    const brand = useBrand();

    return useMemo(() => ({ brand }), [brand]);
}

const appLinks = (rootAdmin: boolean): AppLayoutLink[] => [
    { id: 'dashboard', label: 'Dashboard', destination: { to: '/' }, exact: true, icon: 'layers' },
    ...(rootAdmin
        ? [{ id: 'admin', label: 'Admin', destination: { to: '/panel' }, exact: false, icon: 'user-cog' } as const]
        : []),
    { id: 'account', label: 'Account Settings', destination: { to: '/account' }, exact: false, icon: 'user-round' },
];

const DASHBOARD: AppArea = Object.freeze({ kind: 'dashboard' });

/** The routes that open an area, by id; the theme workbench is an admin page outside the admin layout. */
const AREA_ROUTES: readonly (readonly [string, AppArea['kind']])[] = [
    ['/authenticated/account', 'account'],
    ['/authenticated/server/$id', 'server'],
    ['/authenticated/panel', 'admin'],
    ['/authenticated/panel/theme-workbench', 'admin'],
];

function useAppArea(): AppArea {
    const kind = useRouterState({
        select: ({ matches }) =>
            AREA_ROUTES.find(([id]) => matches.some((match) => match.routeId === id))?.[1] ?? 'dashboard',
    });
    const account = useAccountLayoutModel();
    const server = useServerFrame();
    const admin = useAdminLayoutModel();

    return useMemo(() => {
        if (kind === 'account') {
            return { kind, ...account };
        }

        if (kind === 'admin') {
            return { kind, ...admin };
        }

        // A server that failed to load shows its error on the page, without its navigation.
        return kind === 'server' && server ? { kind, ...server } : DASHBOARD;
    }, [kind, account, server, admin]);
}

function useAppLayoutModel(): AppLayoutModel {
    const brand = useBrand();
    const { uuid, username, email, rootAdmin } = useCurrentUser();
    const { scheme, schemes, setScheme } = useTheme();
    const { mutate, isPending } = useLogout();
    const area = useAppArea();

    return useMemo(
        () => ({
            brand,
            links: appLinks(rootAdmin),
            area,
            user: { uuid, username, email, rootAdmin },
            scheme,
            schemes,
            setScheme: schemes.length > 1 ? setScheme : null,
            signOut: () => mutate({}),
            signingOut: isPending,
        }),
        [brand, area, uuid, username, email, rootAdmin, scheme, schemes, setScheme, mutate, isPending]
    );
}

function useAccountLayoutModel(): AccountLayoutModel {
    const nav = useNavigation('account');

    return useMemo(() => ({ nav }), [nav]);
}

export function serverConflict({ attributes }: Server): ServerConflict | null {
    if (isInstallingStatus(attributes.status)) {
        return 'installing';
    }

    if (attributes.status === 'suspended') {
        return 'suspended';
    }

    if (attributes.is_node_under_maintenance) {
        return 'maintenance';
    }

    if (attributes.is_transferring) {
        return 'transferring';
    }

    return attributes.status === null ? null : 'restoring-backup';
}

const adminLink = (internalId: number, order: number): NavItem => ({
    id: 'admin',
    label: 'Open Admin Area',
    to: '/panel/servers/$id',
    params: { id: String(internalId) },
    icon: 'external-link',
    group: null,
    order,
    exact: false,
});

const runState = (state: ServerPowerState): ServerRunState => (state === 'stopped' ? 'offline' : state);

/** What Wings last reported: the resources query, fetched once and kept current by the server's websocket. */
function useServerRunState(uuid: string): ServerRunState | null {
    const { data } = useQuery({
        ...serverResourceUsageQueryOptions(uuid),
        enabled: uuid !== '',
        staleTime: Infinity,
        select: (resources) => runState(resources.attributes.current_state),
    });

    return data ?? null;
}

/** The model of `layout.server` for the server of the route; null outside a server and while it loads. */
function useServerFrame(): ServerLayoutModel | null {
    const server = useCurrentServer();
    const { rootAdmin } = useCurrentUser();
    const nav = useNavigation('server');
    const state = useServerRunState(server?.attributes.uuid ?? '');

    return useMemo(() => {
        if (!server) {
            return null;
        }

        const { name, identifier, uuid, internal_id: internalId } = server.attributes;

        return {
            name,
            identifier,
            uuid,
            internalId,
            state,
            conflict: serverConflict(server),
            adminLink: rootAdmin ? adminLink(internalId, nav.items.length) : null,
            nav,
            server,
        };
    }, [server, rootAdmin, nav, state]);
}

function useServerLayoutModel(): ServerLayoutModel {
    const model = useServerFrame();

    if (!model) {
        throw new Error('layout.server renders once its server has loaded.');
    }

    return model;
}

function useAdminLayoutModel(): AdminLayoutModel {
    const nav = useNavigation('admin');
    const { uuid, username, email } = useCurrentUser();

    return useMemo(() => ({ nav, user: { uuid, username, email } }), [nav, uuid, username, email]);
}

const noop = () => {};

const brand: LayoutBrand = { name: 'Pterodactyl', logoUrl: null };
const logo: LayoutBrand = { ...brand, logoUrl: '/favicons/android-chrome-192x192.png' };

const appLayoutFixture = (
    rootAdmin: boolean,
    schemes: readonly ThemeScheme[] = ['dark'],
    area: AppArea = DASHBOARD
): AppLayoutModel => ({
    brand,
    links: appLinks(rootAdmin),
    area,
    user: { uuid: '11111111-1111-4111-8111-111111111111', username: 'alex', email: 'alex@example.test', rootAdmin },
    scheme: schemes[0] ?? 'dark',
    schemes,
    setScheme: schemes.length > 1 ? noop : null,
    signOut: noop,
    signingOut: false,
});

const SERVER_ID = 'a1b2c3d4';

/** Core items of an area as `useNavigation` lists them, from `[path, label, icon, options]`. */
function navFixture(
    area: NavArea,
    entries: readonly (readonly [string, string, string, Partial<NavItem>?])[]
): NavigationModel {
    return {
        items: entries.map(([path, label, icon, options], order) => ({
            id: path || 'index',
            label,
            to: path ? `${SCREEN_ROOTS[area]}/${path}` : SCREEN_ROOTS[area],
            params: area === 'server' ? { id: SERVER_ID } : {},
            icon,
            group: null,
            order,
            exact: path === '',
            ...options,
        })),
    };
}

const extensionItem = (area: NavArea, order: number): Partial<NavItem> => ({
    id: 'tickets',
    extensionId: 'support',
    icon: 'life-buoy',
    group: 'Support',
    order,
    to: `${SCREEN_ROOTS[area]}/tickets`,
});

const accountNav = (extension = false) =>
    navFixture('account', [
        ['', 'Account', 'user-round'],
        ['api', 'API Credentials', 'key-round'],
        ['ssh', 'SSH Keys', 'terminal'],
        ['activity', 'Activity', 'history'],
        ...(extension ? [['tickets', 'Tickets', 'life-buoy', extensionItem('account', 4)] as const] : []),
    ]);

const serverPaths = [
    ['', 'Console', 'square-terminal'],
    ['files', 'Files', 'folder', { hash: '/' }],
    ['databases', 'Databases', 'database'],
    ['schedules', 'Schedules', 'calendar-clock'],
    ['users', 'Users', 'users'],
    ['backups', 'Backups', 'archive'],
    ['network', 'Network', 'network'],
    ['startup', 'Startup', 'rocket'],
    ['settings', 'Settings', 'settings'],
    ['activity', 'Activity', 'history'],
] as const;

function serverLayoutFixture(
    options: {
        status?: Server['attributes']['status'];
        state?: ServerRunState | null;
        rootAdmin?: boolean;
        allowed?: readonly string[];
    } = {}
): ServerLayoutModel {
    const server = createTestServer({ identifier: SERVER_ID, name: 'Survival', status: options.status ?? null });
    const { items } = navFixture('server', serverPaths);
    const nav = { items: items.filter((item) => !options.allowed || options.allowed.includes(item.id)) };

    return {
        name: server.attributes.name,
        identifier: SERVER_ID,
        uuid: server.attributes.uuid,
        internalId: server.attributes.internal_id,
        state: options.state === undefined ? 'running' : options.state,
        conflict: serverConflict(server),
        adminLink: options.rootAdmin ? adminLink(server.attributes.internal_id, nav.items.length) : null,
        nav,
        server,
    };
}

const adminNav = (extension = false) =>
    navFixture('admin', [
        ['', 'Overview', 'layout-dashboard'],
        ['activity', 'Activity', 'history'],
        ['settings', 'Settings', 'settings'],
        ['users', 'Users', 'users'],
        ['locations', 'Locations', 'map-pin'],
        ['nodes', 'Nodes', 'network'],
        ['servers', 'Servers', 'server'],
        ['databases', 'Database Hosts', 'database'],
        ['mounts', 'Mounts', 'folder-tree'],
        ['eggs', 'Eggs', 'egg'],
        ['tags', 'Tags', 'tag'],
        ['extensions', 'Extensions', 'blocks'],
        ['themes', 'Themes', 'palette'],
        ['api', 'API Keys', 'key-round'],
        ...(extension ? [['tickets', 'Tickets', 'life-buoy', extensionItem('admin', 14)] as const] : []),
    ]);

const adminUser = { uuid: '11111111-1111-4111-8111-111111111111', username: 'alex', email: 'alex@example.test' };

implementViews({
    'layout.auth': {
        use: useAuthLayoutModel,
        fixtures: { default: () => ({ brand }), logo: () => ({ brand: logo }) },
    },
    'layout.app': {
        use: useAppLayoutModel,
        fixtures: {
            user: () => appLayoutFixture(false),
            admin: () => appLayoutFixture(true),
            schemes: () => appLayoutFixture(false, ['dark', 'light']),
            logo: () => ({ ...appLayoutFixture(false), brand: logo }),
            accountArea: () => appLayoutFixture(false, ['dark'], { kind: 'account', nav: accountNav() }),
            serverArea: () => appLayoutFixture(false, ['dark'], { kind: 'server', ...serverLayoutFixture() }),
            adminArea: () => appLayoutFixture(true, ['dark'], { kind: 'admin', nav: adminNav(), user: adminUser }),
        },
    },
    'layout.account': {
        use: useAccountLayoutModel,
        fixtures: { default: () => ({ nav: accountNav() }), extension: () => ({ nav: accountNav(true) }) },
    },
    'layout.server': {
        use: useServerLayoutModel,
        fixtures: {
            owner: () => serverLayoutFixture(),
            subuser: () => serverLayoutFixture({ allowed: ['index', 'files', 'backups'] }),
            admin: () => serverLayoutFixture({ rootAdmin: true }),
            offline: () => serverLayoutFixture({ state: 'offline' }),
            installing: () => serverLayoutFixture({ status: 'installing', state: 'offline' }),
            suspended: () => serverLayoutFixture({ status: 'suspended', state: 'offline' }),
        },
    },
    'layout.admin': {
        use: useAdminLayoutModel,
        fixtures: {
            default: () => ({ nav: adminNav(), user: adminUser }),
            extension: () => ({ nav: adminNav(true), user: adminUser }),
        },
    },
});
