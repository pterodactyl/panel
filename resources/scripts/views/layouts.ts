import { useMemo } from 'react';
import { useCurrentUser } from '@/api/account/queries';
import { useLogout } from '@/api/auth/queries';
import { useSiteSettings } from '@/api/settings/queries';
import type { CorePanelDestination } from '@/sdk/navigation';
import { defineView } from './registry';

/** A model with nothing in it yet: the view's default renders the existing container as is. */
export type OpaqueModel = Readonly<Record<never, never>>;
export const opaqueModel: OpaqueModel = Object.freeze({});
export const useOpaqueModel = (): OpaqueModel => opaqueModel;

export interface AppLayoutLink {
    readonly id: 'dashboard' | 'admin' | 'account';
    readonly label: string;
    readonly destination: CorePanelDestination;
    /** Active only on the destination itself, not below it. */
    readonly exact: boolean;
}
export interface AppLayoutModel {
    readonly brand: { readonly name: string };
    /** The dashboard, the admin area for root admins, and the account. */
    readonly links: readonly AppLayoutLink[];
    readonly user: {
        readonly uuid: string;
        readonly username: string;
        readonly email: string;
        readonly rootAdmin: boolean;
    };
    readonly signOut: () => void;
    readonly signingOut: boolean;
}

declare module './registry' {
    interface ViewModels {
        'layout.auth': OpaqueModel;
        'layout.app': AppLayoutModel;
        'layout.account': OpaqueModel;
        'layout.server': OpaqueModel;
        'layout.admin': OpaqueModel;
        'app.search': OpaqueModel;
    }
}

function useAppLayoutModel(): AppLayoutModel {
    const { name } = useSiteSettings();
    const { uuid, username, email, rootAdmin } = useCurrentUser();
    const { mutate, isPending } = useLogout();

    return useMemo(() => {
        const links: AppLayoutLink[] = [{ id: 'dashboard', label: 'Dashboard', destination: { to: '/' }, exact: true }];

        if (rootAdmin) {
            links.push({ id: 'admin', label: 'Admin', destination: { to: '/panel' }, exact: false });
        }

        links.push({ id: 'account', label: 'Account Settings', destination: { to: '/account' }, exact: false });

        return {
            brand: { name },
            links,
            user: { uuid, username, email, rootAdmin },
            signOut: () => mutate({}),
            signingOut: isPending,
        };
    }, [name, uuid, username, email, rootAdmin, mutate, isPending]);
}

const noop = () => {};

const appLayoutFixture = (rootAdmin: boolean): AppLayoutModel => ({
    brand: { name: 'Pterodactyl' },
    links: [
        { id: 'dashboard', label: 'Dashboard', destination: { to: '/' }, exact: true },
        ...(rootAdmin ? [{ id: 'admin', label: 'Admin', destination: { to: '/panel' }, exact: false } as const] : []),
        { id: 'account', label: 'Account Settings', destination: { to: '/account' }, exact: false },
    ],
    user: { uuid: '11111111-1111-4111-8111-111111111111', username: 'alex', email: 'alex@example.test', rootAdmin },
    signOut: noop,
    signingOut: false,
});

export const layoutViews = [
    defineView('layout.auth', { context: 'auth', use: useOpaqueModel }),
    defineView('layout.app', {
        context: 'app',
        use: useAppLayoutModel,
        slots: ['nav.items.before', 'nav.items.after'],
        fixtures: { user: () => appLayoutFixture(false), admin: () => appLayoutFixture(true) },
    }),
    defineView('layout.account', {
        context: 'account',
        use: useOpaqueModel,
        slots: ['account.navigation.before', 'account.navigation.after'],
    }),
    defineView('layout.server', {
        context: 'server',
        use: useOpaqueModel,
        slots: ['server.navigation.before', 'server.navigation.after'],
    }),
    defineView('layout.admin', {
        context: 'admin',
        use: useOpaqueModel,
        slots: ['panel.navigation.before', 'panel.navigation.after'],
    }),
    defineView('app.search', { context: 'app', use: useOpaqueModel }),
];
