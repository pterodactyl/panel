import { createElement } from 'react';
import Spinner from '@/components/elements/Spinner';
import { VIEW_PAGES, viewPage } from './pages';
import { defineView } from './registry';

// Every core view up front: its context, slots and placeholder. Hooks and fixtures stay in each area's module,
// which loads with the area's routes and implements its views with `implementViews`.
const layouts = { load: () => import('./layouts') };
const search = { load: () => import('./search') };
const dashboard = { load: () => import('./dashboard') };
const login = { load: () => import('./auth/login') };
const password = { load: () => import('./auth/password') };
const overview = { load: () => import('./account/overview') };
const keys = { load: () => import('./account/keys') };
const activity = { load: () => import('./activity') };
const backups = { load: () => import('./server/backups') };
const conflict = { load: () => import('./server/conflict') };
const serverConsole = { load: () => import('./server/console') };
const databases = { load: () => import('./server/databases') };
const features = { load: () => import('./server/features') };
const files = { load: () => import('./server/files') };
const network = { load: () => import('./server/network') };
const schedules = { load: () => import('./server/schedules') };
const settings = { load: () => import('./server/settings') };
const startup = { load: () => import('./server/startup') };
const users = { load: () => import('./server/users') };

const spinner = createElement(Spinner, { size: 'large', centered: true });

defineView('layout.auth', { context: 'auth', ...layouts });
defineView('layout.app', { context: 'app', slots: ['nav.items.before', 'nav.items.after'], ...layouts });
defineView('layout.account', {
    context: 'account',
    slots: ['account.navigation.before', 'account.navigation.after'],
    ...layouts,
});
defineView('layout.server', {
    context: 'server',
    slots: ['server.navigation.before', 'server.navigation.after'],
    ...layouts,
});
defineView('layout.admin', {
    context: 'admin',
    slots: ['panel.navigation.before', 'panel.navigation.after'],
    ...layouts,
});
defineView('app.search', { context: 'app', ...search });

defineView('page.dashboard', { context: 'app', ...dashboard });
defineView('dashboard.serverList', {
    context: 'app',
    slots: [
        'dashboard.before',
        'dashboard.after',
        'dashboard.serverRow.before',
        'dashboard.serverRow.metrics.after',
        'dashboard.serverRow.after',
    ],
    ...dashboard,
});
defineView('dashboard.serverCard', {
    context: 'app',
    placeholder: createElement('div', {
        className: 'h-12 animate-pulse rounded-sm bg-muted',
        'aria-label': 'Loading server card',
    }),
    ...dashboard,
});
defineView('dashboard.serverCard.identity', {
    context: 'app',
    slots: ['dashboard.serverRow.name.after'],
    ...dashboard,
});
defineView('dashboard.serverCard.address', { context: 'app', ...dashboard });
defineView('dashboard.serverCard.metrics', { context: 'app', ...dashboard });

defineView('page.auth.login', {
    ...VIEW_PAGES['page.auth.login'],
    slots: ['auth.login.before', 'auth.login.after', 'auth.login.form.after'],
    ...login,
});
defineView('page.auth.checkpoint', { ...VIEW_PAGES['page.auth.checkpoint'], ...login });
defineView('page.auth.password', { ...VIEW_PAGES['page.auth.password'], ...password });
defineView('page.auth.passwordReset', { ...VIEW_PAGES['page.auth.passwordReset'], ...password });

defineView('page.account.overview', {
    ...VIEW_PAGES['page.account.overview'],
    slots: ['account.overview.before', 'account.overview.after'],
    ...overview,
});
defineView('account.password', { context: 'account', ...overview });
defineView('account.email', { context: 'account', ...overview });
defineView('account.avatar', { context: 'account', ...overview });
defineView('account.twoFactor', { context: 'account', ...overview });
defineView('page.account.api', { ...VIEW_PAGES['page.account.api'], ...keys });
defineView('page.account.ssh', { ...VIEW_PAGES['page.account.ssh'], ...keys });
defineView('account.apiKeys.list', { context: 'account', ...keys });
defineView('account.apiKeys.create', { context: 'account', ...keys });
defineView('account.sshKeys.list', { context: 'account', ...keys });
defineView('account.sshKeys.create', { context: 'account', ...keys });

defineView('activity.log', { context: 'app', ...activity });
defineView('page.account.activity', { ...VIEW_PAGES['page.account.activity'], ...activity });
defineView('page.server.activity', { ...VIEW_PAGES['page.server.activity'], ...activity });

defineView('server.conflict', { context: 'server', ...conflict });

defineView('page.server.console', {
    ...viewPage('page.server.console'),
    slots: ['server.console.before', 'server.console.after'],
    ...serverConsole,
});
defineView('server.terminal', { context: 'server', ...serverConsole });
defineView('server.power', {
    context: 'server',
    slots: ['server.console.power.before', 'server.console.power.after'],
    ...serverConsole,
});
defineView('server.stats', { context: 'server', ...serverConsole });
defineView('server.graphs', { context: 'server', ...serverConsole });
defineView('server.feature.eula', { context: 'server', ...features });
defineView('server.feature.javaVersion', { context: 'server', ...features });
defineView('server.feature.gslToken', { context: 'server', ...features });
defineView('server.feature.pidLimit', { context: 'server', ...features });
defineView('server.feature.steamDiskSpace', { context: 'server', ...features });
defineView('server.feature.hytaleOauth', { context: 'server', ...features });

defineView('page.server.files', {
    ...viewPage('page.server.files'),
    slots: ['server.files.before', 'server.files.after'],
    ...files,
});
defineView('server.files.manager', { context: 'server', placeholder: spinner, ...files });
defineView('server.files.manager.toolbar', { context: 'server', slots: ['server.files.toolbar'], ...files });
defineView('server.files.manager.list', { context: 'server', slots: ['server.files.rowActions'], ...files });
defineView('server.files.manager.selection', { context: 'server', slots: ['server.files.selectionActions'], ...files });
defineView('server.files.details', {
    context: 'server',
    placeholder: createElement('div', {
        className: 'h-5 w-full animate-pulse rounded-sm bg-muted',
        'aria-label': 'Loading file details',
    }),
    ...files,
});
defineView('page.server.files.editor', { ...viewPage('page.server.files.editor'), ...files });
defineView('server.files.editor', { context: 'server', placeholder: spinner, ...files });
defineView('server.files.editor.notice', { context: 'server', ...files });
defineView('server.files.editor.canvas', { context: 'server', ...files });
defineView('server.files.editor.language', { context: 'server', ...files });
defineView('server.files.editor.actions', { context: 'server', ...files });

defineView('page.server.databases', { ...viewPage('page.server.databases'), ...databases });
defineView('server.databases', { context: 'server', ...databases });

defineView('page.server.schedules', { ...viewPage('page.server.schedules'), ...schedules });
defineView('server.schedules', { context: 'server', ...schedules });
defineView('page.server.schedules.detail', { ...viewPage('page.server.schedules.detail'), ...schedules });
defineView('server.schedule', { context: 'server', ...schedules });
defineView('server.schedule.tasks', { context: 'server', ...schedules });

defineView('page.server.users', { ...viewPage('page.server.users'), ...users });
defineView('server.subusers', { context: 'server', ...users });
defineView('page.server.users.create', { ...viewPage('page.server.users.create'), ...users });
defineView('server.subuserForm', { context: 'server', slots: ['server.users.permissions.before'], ...users });

defineView('page.server.backups', { ...viewPage('page.server.backups'), ...backups });
defineView('server.backups', { context: 'server', ...backups });

defineView('page.server.network', { ...viewPage('page.server.network'), ...network });
defineView('server.allocations', { context: 'server', ...network });

defineView('page.server.startup', {
    ...viewPage('page.server.startup'),
    slots: ['server.startup.before', 'server.startup.after', 'server.startup.form'],
    ...startup,
});
defineView('server.startup.command', { context: 'server', ...startup });
defineView('server.startup.image', { context: 'server', ...startup });
defineView('server.startup.variables', { context: 'server', ...startup });

defineView('page.server.settings', { ...viewPage('page.server.settings'), ...settings });
defineView('server.sftp', { context: 'server', ...settings });
defineView('server.debug', { context: 'server', ...settings });
defineView('server.rename', { context: 'server', ...settings });
defineView('server.reinstall', { context: 'server', ...settings });
