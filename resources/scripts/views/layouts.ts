import { useMemo } from 'react';
import { useCurrentUser } from '@/api/account/queries';
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
}
export interface AppLayoutModel {
    readonly brand: LayoutBrand;
    /** The dashboard, the admin area for root admins, and the account. */
    readonly links: readonly AppLayoutLink[];
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
    /** The server's id in the admin area. */
    readonly internalId: number;
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

function useAppLayoutModel(): AppLayoutModel {
    const brand = useBrand();
    const { uuid, username, email, rootAdmin } = useCurrentUser();
    const { scheme, schemes, setScheme } = useTheme();
    const { mutate, isPending } = useLogout();

    return useMemo(() => {
        const links: AppLayoutLink[] = [{ id: 'dashboard', label: 'Dashboard', destination: { to: '/' }, exact: true }];

        if (rootAdmin) {
            links.push({ id: 'admin', label: 'Admin', destination: { to: '/panel' }, exact: false });
        }

        links.push({ id: 'account', label: 'Account Settings', destination: { to: '/account' }, exact: false });

        return {
            brand,
            links,
            user: { uuid, username, email, rootAdmin },
            scheme,
            schemes,
            setScheme: schemes.length > 1 ? setScheme : null,
            signOut: () => mutate({}),
            signingOut: isPending,
        };
    }, [brand, uuid, username, email, rootAdmin, scheme, schemes, setScheme, mutate, isPending]);
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

function useServerLayoutModel(): ServerLayoutModel {
    const server = useCurrentServer();
    const { rootAdmin } = useCurrentUser();
    const nav = useNavigation('server');
    const model = useMemo(() => {
        if (!server) {
            return null;
        }

        const { name, identifier, internal_id: internalId } = server.attributes;

        return {
            name,
            identifier,
            internalId,
            conflict: serverConflict(server),
            adminLink: rootAdmin ? adminLink(internalId, nav.items.length) : null,
            nav,
            server,
        };
    }, [server, rootAdmin, nav]);

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

const appLayoutFixture = (rootAdmin: boolean, schemes: readonly ThemeScheme[] = ['dark']): AppLayoutModel => ({
    brand,
    links: [
        { id: 'dashboard', label: 'Dashboard', destination: { to: '/' }, exact: true },
        ...(rootAdmin ? [{ id: 'admin', label: 'Admin', destination: { to: '/panel' }, exact: false } as const] : []),
        { id: 'account', label: 'Account Settings', destination: { to: '/account' }, exact: false },
    ],
    user: { uuid: '11111111-1111-4111-8111-111111111111', username: 'alex', email: 'alex@example.test', rootAdmin },
    scheme: schemes[0] ?? 'dark',
    schemes,
    setScheme: schemes.length > 1 ? noop : null,
    signOut: noop,
    signingOut: false,
});

const SERVER_ID = 'a1b2c3d4';

/** Core items of an area as `useNavigation` lists them, from `[path, label, options]`. */
function navFixture(
    area: NavArea,
    entries: readonly (readonly [string, string, Partial<NavItem>?])[]
): NavigationModel {
    return {
        items: entries.map(([path, label, options], order) => ({
            id: path || 'index',
            label,
            to: path ? `${SCREEN_ROOTS[area]}/${path}` : SCREEN_ROOTS[area],
            params: area === 'server' ? { id: SERVER_ID } : {},
            icon: null,
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
        ['', 'Account'],
        ['api', 'API Credentials'],
        ['ssh', 'SSH Keys'],
        ['activity', 'Activity'],
        ...(extension ? [['tickets', 'Tickets', extensionItem('account', 4)] as const] : []),
    ]);

const serverPaths = [
    ['', 'Console'],
    ['files', 'Files', { hash: '/' }],
    ['databases', 'Databases'],
    ['schedules', 'Schedules'],
    ['users', 'Users'],
    ['backups', 'Backups'],
    ['network', 'Network'],
    ['startup', 'Startup'],
    ['settings', 'Settings'],
    ['activity', 'Activity'],
] as const;

function serverLayoutFixture(
    options: { status?: Server['attributes']['status']; rootAdmin?: boolean; allowed?: readonly string[] } = {}
): ServerLayoutModel {
    const server = createTestServer({ identifier: SERVER_ID, name: 'Survival', status: options.status ?? null });
    const { items } = navFixture('server', serverPaths);
    const nav = { items: items.filter((item) => !options.allowed || options.allowed.includes(item.id)) };

    return {
        name: server.attributes.name,
        identifier: SERVER_ID,
        internalId: server.attributes.internal_id,
        conflict: serverConflict(server),
        adminLink: options.rootAdmin ? adminLink(server.attributes.internal_id, nav.items.length) : null,
        nav,
        server,
    };
}

const adminNav = (extension = false) =>
    navFixture('admin', [
        ['', 'Overview', { icon: 'layout-dashboard' }],
        ['activity', 'Activity'],
        ['settings', 'Settings', { icon: 'settings' }],
        ['users', 'Users', { icon: 'users' }],
        ['locations', 'Locations', { icon: 'map-pin' }],
        ['nodes', 'Nodes', { icon: 'network' }],
        ['servers', 'Servers', { icon: 'server' }],
        ['databases', 'Database Hosts', { icon: 'database' }],
        ['mounts', 'Mounts', { icon: 'folder-tree' }],
        ['eggs', 'Eggs', { icon: 'egg' }],
        ['tags', 'Tags', { icon: 'tag' }],
        ['extensions', 'Extensions', { icon: 'blocks' }],
        ['themes', 'Themes', { icon: 'palette' }],
        ['api', 'API Keys', { icon: 'key-round' }],
        ...(extension ? [['tickets', 'Tickets', extensionItem('admin', 14)] as const] : []),
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
            installing: () => serverLayoutFixture({ status: 'installing' }),
            suspended: () => serverLayoutFixture({ status: 'suspended' }),
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
