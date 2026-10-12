import { createElement } from 'react';
import Spinner from '@/components/elements/Spinner';
import type { RouteSlotName } from '@/extensions/registry';
import { getObjectKeys } from '@/lib/objects';
import { useOpaqueModel, type OpaqueModel } from './layouts';
import { defineView, type ViewContext } from './registry';

interface PageSpec {
    readonly context: ViewContext;
    /** Rendered by the engine before and after the page view. */
    readonly slots?: readonly [RouteSlotName, RouteSlotName];
}

/** Routed pages that are real views, defined beside their blocks. */
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

    'page.server.activity': { context: 'server', slots: ['server.activity.before', 'server.activity.after'] },
} as const satisfies Record<`page.${string}`, PageSpec>;

/** Every routed page whose default is still the existing container. */
export const OPAQUE_PAGES = {
    'page.server.console': { context: 'server' },
    'page.server.files': { context: 'server' },
    'page.server.files.editor': {
        context: 'server',
        slots: ['server.files.editor.before', 'server.files.editor.after'],
    },
    'page.server.databases': { context: 'server', slots: ['server.databases.before', 'server.databases.after'] },
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
    'page.server.network': { context: 'server', slots: ['server.network.before', 'server.network.after'] },
    'page.server.startup': { context: 'server', slots: ['server.startup.before', 'server.startup.after'] },
    'page.server.settings': { context: 'server', slots: ['server.settings.before', 'server.settings.after'] },

    'page.admin.overview': { context: 'admin' },
    'page.admin.activity': { context: 'admin', slots: ['panel.activity.before', 'panel.activity.after'] },
    'page.admin.settings': { context: 'admin', slots: ['panel.settings.before', 'panel.settings.after'] },
    'page.admin.settings.mail': { context: 'admin', slots: ['panel.settings.before', 'panel.settings.after'] },
    'page.admin.settings.advanced': { context: 'admin', slots: ['panel.settings.before', 'panel.settings.after'] },
    'page.admin.users': { context: 'admin', slots: ['panel.users.before', 'panel.users.after'] },
    'page.admin.users.create': { context: 'admin', slots: ['panel.users.create.before', 'panel.users.create.after'] },
    'page.admin.users.detail': { context: 'admin', slots: ['panel.users.detail.before', 'panel.users.detail.after'] },
    'page.admin.users.detail.about': { context: 'admin' },
    'page.admin.locations': { context: 'admin', slots: ['panel.locations.before', 'panel.locations.after'] },
    'page.admin.locations.detail': {
        context: 'admin',
        slots: ['panel.locations.detail.before', 'panel.locations.detail.after'],
    },
    'page.admin.nodes': { context: 'admin', slots: ['panel.nodes.before', 'panel.nodes.after'] },
    'page.admin.nodes.create': { context: 'admin', slots: ['panel.nodes.create.before', 'panel.nodes.create.after'] },
    'page.admin.nodes.detail': { context: 'admin', slots: ['panel.nodes.detail.before', 'panel.nodes.detail.after'] },
    'page.admin.nodes.detail.about': {
        context: 'admin',
        slots: ['panel.nodes.detail.about.before', 'panel.nodes.detail.about.after'],
    },
    'page.admin.nodes.detail.settings': {
        context: 'admin',
        slots: ['panel.nodes.detail.settings.before', 'panel.nodes.detail.settings.after'],
    },
    'page.admin.nodes.detail.configuration': {
        context: 'admin',
        slots: ['panel.nodes.detail.configuration.before', 'panel.nodes.detail.configuration.after'],
    },
    'page.admin.nodes.detail.allocations': {
        context: 'admin',
        slots: ['panel.nodes.detail.allocations.before', 'panel.nodes.detail.allocations.after'],
    },
    'page.admin.nodes.detail.tags': {
        context: 'admin',
        slots: ['panel.nodes.detail.tags.before', 'panel.nodes.detail.tags.after'],
    },
    'page.admin.nodes.detail.servers': {
        context: 'admin',
        slots: ['panel.nodes.detail.servers.before', 'panel.nodes.detail.servers.after'],
    },
    'page.admin.servers': { context: 'admin', slots: ['panel.servers.before', 'panel.servers.after'] },
    'page.admin.servers.create': {
        context: 'admin',
        slots: ['panel.servers.create.before', 'panel.servers.create.after'],
    },
    'page.admin.servers.detail': {
        context: 'admin',
        slots: ['panel.servers.detail.before', 'panel.servers.detail.after'],
    },
    'page.admin.servers.detail.about': {
        context: 'admin',
        slots: ['panel.servers.detail.about.before', 'panel.servers.detail.about.after'],
    },
    'page.admin.servers.detail.details': {
        context: 'admin',
        slots: ['panel.servers.detail.details.before', 'panel.servers.detail.details.after'],
    },
    'page.admin.servers.detail.build': {
        context: 'admin',
        slots: ['panel.servers.detail.build.before', 'panel.servers.detail.build.after'],
    },
    'page.admin.servers.detail.startup': {
        context: 'admin',
        slots: ['panel.servers.detail.startup.before', 'panel.servers.detail.startup.after'],
    },
    'page.admin.servers.detail.databases': {
        context: 'admin',
        slots: ['panel.servers.detail.databases.before', 'panel.servers.detail.databases.after'],
    },
    'page.admin.servers.detail.mounts': {
        context: 'admin',
        slots: ['panel.servers.detail.mounts.before', 'panel.servers.detail.mounts.after'],
    },
    'page.admin.servers.detail.manage': {
        context: 'admin',
        slots: ['panel.servers.detail.manage.before', 'panel.servers.detail.manage.after'],
    },
    'page.admin.servers.detail.delete': {
        context: 'admin',
        slots: ['panel.servers.detail.delete.before', 'panel.servers.detail.delete.after'],
    },
    'page.admin.databaseHosts': {
        context: 'admin',
        slots: ['panel.databaseHosts.before', 'panel.databaseHosts.after'],
    },
    'page.admin.databaseHosts.create': {
        context: 'admin',
        slots: ['panel.databaseHosts.create.before', 'panel.databaseHosts.create.after'],
    },
    'page.admin.databaseHosts.detail': {
        context: 'admin',
        slots: ['panel.databaseHosts.detail.before', 'panel.databaseHosts.detail.after'],
    },
    'page.admin.mounts': { context: 'admin', slots: ['panel.mounts.before', 'panel.mounts.after'] },
    'page.admin.mounts.create': {
        context: 'admin',
        slots: ['panel.mounts.create.before', 'panel.mounts.create.after'],
    },
    'page.admin.mounts.detail': {
        context: 'admin',
        slots: ['panel.mounts.detail.before', 'panel.mounts.detail.after'],
    },
    'page.admin.eggs': { context: 'admin', slots: ['panel.eggs.before', 'panel.eggs.after'] },
    'page.admin.eggs.create': { context: 'admin', slots: ['panel.eggs.create.before', 'panel.eggs.create.after'] },
    'page.admin.eggs.catalog': { context: 'admin' },
    'page.admin.eggs.detail': { context: 'admin', slots: ['panel.eggs.detail.before', 'panel.eggs.detail.after'] },
    'page.admin.eggs.detail.configuration': {
        context: 'admin',
        slots: ['panel.eggs.detail.configuration.before', 'panel.eggs.detail.configuration.after'],
    },
    'page.admin.eggs.detail.tags': {
        context: 'admin',
        slots: ['panel.eggs.detail.tags.before', 'panel.eggs.detail.tags.after'],
    },
    'page.admin.eggs.detail.variables': {
        context: 'admin',
        slots: ['panel.eggs.detail.variables.before', 'panel.eggs.detail.variables.after'],
    },
    'page.admin.eggs.detail.script': {
        context: 'admin',
        slots: ['panel.eggs.detail.script.before', 'panel.eggs.detail.script.after'],
    },
    'page.admin.tags': { context: 'admin', slots: ['panel.tags.before', 'panel.tags.after'] },
    'page.admin.extensions': { context: 'admin', slots: ['panel.extensions.before', 'panel.extensions.after'] },
    'page.admin.themes': { context: 'admin', slots: ['panel.themes.before', 'panel.themes.after'] },
    'page.admin.themeWorkbench': { context: 'admin' },
    'page.admin.apiKeys': { context: 'admin', slots: ['panel.apiKeys.before', 'panel.apiKeys.after'] },
} as const satisfies Record<`page.${string}`, PageSpec>;

export type OpaquePageName = keyof typeof OPAQUE_PAGES;
export type ViewPageName = keyof typeof VIEW_PAGES;
export type PageViewName = OpaquePageName | ViewPageName;

const isViewPage = (name: PageViewName): name is ViewPageName => Object.hasOwn(VIEW_PAGES, name);

declare module './registry' {
    interface ViewModels extends Record<OpaquePageName, OpaqueModel> {}
    interface ViewInputs {
        /** The console stays mounted while another server page shows. */
        'page.server.console': { readonly active: boolean };
    }
}

/** The engine-rendered slots of a page, if it has any. */
export function pageSlots(name: PageViewName): readonly [RouteSlotName, RouteSlotName] | undefined {
    const spec: PageSpec = isViewPage(name) ? VIEW_PAGES[name] : OPAQUE_PAGES[name];

    return spec.slots;
}

const placeholder = createElement(Spinner, { centered: true });

export const pageViews = getObjectKeys(OPAQUE_PAGES).map((name) => {
    const spec: PageSpec = OPAQUE_PAGES[name];

    return defineView(name, { context: spec.context, use: useOpaqueModel, slots: spec.slots, placeholder });
});
