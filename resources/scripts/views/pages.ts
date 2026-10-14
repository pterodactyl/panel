import { createElement } from 'react';
import Spinner from '@/components/elements/Spinner';
import type { RouteSlotName } from '@/extensions/registry';
import type { ViewContext } from './registry';

/** The slots rendered before and after a routed page. */
export type PageSlots = readonly [RouteSlotName, RouteSlotName];

interface PageSpec {
    readonly context: ViewContext;
    /** Rendered by the engine before and after the page view. */
    readonly slots?: PageSlots;
}

/** Routed pages that are views, defined beside their blocks. Admin pages are plain components. */
export const VIEW_PAGES = {
    'page.dashboard': { context: 'app' },

    'page.auth.login': { context: 'auth', slots: ['auth.login.before', 'auth.login.after'] },
    'page.auth.checkpoint': { context: 'auth', slots: ['auth.checkpoint.before', 'auth.checkpoint.after'] },
    'page.auth.password': { context: 'auth', slots: ['auth.password.before', 'auth.password.after'] },
    'page.auth.passwordReset': { context: 'auth', slots: ['auth.passwordReset.before', 'auth.passwordReset.after'] },

    'page.account.overview': { context: 'account' },
    'page.account.api': { context: 'account', slots: ['account.api.before', 'account.api.after'] },
    'page.account.ssh': { context: 'account', slots: ['account.ssh.before', 'account.ssh.after'] },
    'page.account.activity': { context: 'account', slots: ['account.activity.before', 'account.activity.after'] },

    'page.server.console': { context: 'server' },
    'page.server.files': { context: 'server' },
    'page.server.files.editor': {
        context: 'server',
        slots: ['server.files.editor.before', 'server.files.editor.after'],
    },
    'page.server.databases': { context: 'server', slots: ['server.databases.before', 'server.databases.after'] },
    'page.server.network': { context: 'server', slots: ['server.network.before', 'server.network.after'] },
    'page.server.startup': { context: 'server', slots: ['server.startup.before', 'server.startup.after'] },
    'page.server.activity': { context: 'server', slots: ['server.activity.before', 'server.activity.after'] },
    'page.server.schedules': { context: 'server', slots: ['server.schedules.before', 'server.schedules.after'] },
    'page.server.schedules.detail': {
        context: 'server',
        slots: ['server.schedules.detail.before', 'server.schedules.detail.after'],
    },
    'page.server.users': { context: 'server', slots: ['server.users.before', 'server.users.after'] },
    'page.server.users.create': {
        context: 'server',
        slots: ['server.users.create.before', 'server.users.create.after'],
    },
    'page.server.backups': { context: 'server', slots: ['server.backups.before', 'server.backups.after'] },
    'page.server.settings': { context: 'server', slots: ['server.settings.before', 'server.settings.after'] },
} as const satisfies Record<`page.${string}`, PageSpec>;

export type ViewPageName = keyof typeof VIEW_PAGES;

/** The engine-rendered slots of a page, if it has any. */
export function pageSlots(name: ViewPageName): PageSlots | undefined {
    const spec: PageSpec = VIEW_PAGES[name];

    return spec.slots;
}

const placeholder = createElement(Spinner, { centered: true });

/** What a page's definition shares with the others: its context, slots and placeholder. */
export const viewPage = (name: ViewPageName) => ({ ...VIEW_PAGES[name], placeholder });
