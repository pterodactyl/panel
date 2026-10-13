import { lazyView, type ThemeViews } from '@/sdk/theme';
import AuthLayout from './layout/AuthLayout';

const dashboard = () => import('./dashboard');
const auth = () => import('./auth');
const account = () => import('./account');
const activity = () => import('./activity');
const schedules = () => import('./server/schedules');
const users = () => import('./server/users');
const backups = () => import('./server/backups');
const settings = () => import('./server/settings');
const serverConsole = () => import('./server/console');
const files = () => import('./server/files');
const databases = () => import('./server/databases');
const network = () => import('./server/network');
const startup = () => import('./server/startup');

/** Classic's own views; the rest still render the existing containers as their default. */
export const classicViews: ThemeViews = {
    // The sign-in screen renders without waiting for a chunk.
    'layout.auth': AuthLayout,
    'layout.app': lazyView(() => import('./layout/AppLayout')),
    'layout.account': lazyView(() => import('./layout/AccountLayout')),
    'layout.server': lazyView(() => import('./layout/ServerLayout')),
    'layout.admin': lazyView(() => import('./layout/AdminLayout')),
    'app.search': lazyView(() => import('./layout/Search')),
    'page.dashboard': lazyView(dashboard, 'Dashboard'),
    'dashboard.serverList': lazyView(dashboard, 'ServerList'),
    'dashboard.serverCard': lazyView(dashboard, 'ServerCard'),

    'page.auth.login': lazyView(auth, 'LoginPage'),
    'page.auth.checkpoint': lazyView(auth, 'CheckpointPage'),
    'page.auth.password': lazyView(auth, 'ForgotPasswordPage'),
    'page.auth.passwordReset': lazyView(auth, 'ResetPasswordPage'),

    'page.account.overview': lazyView(account, 'AccountOverview'),
    'account.password': lazyView(account, 'PasswordCard'),
    'account.email': lazyView(account, 'EmailCard'),
    'account.twoFactor': lazyView(account, 'TwoFactorCard'),
    'page.account.api': lazyView(account, 'ApiKeysPage'),
    'account.apiKeys.list': lazyView(account, 'ApiKeyList'),
    'account.apiKeys.create': lazyView(account, 'ApiKeyCreate'),
    'page.account.ssh': lazyView(account, 'SshKeysPage'),
    'account.sshKeys.list': lazyView(account, 'SshKeyList'),
    'account.sshKeys.create': lazyView(account, 'SshKeyCreate'),

    'activity.log': lazyView(activity, 'ActivityLog'),
    'page.account.activity': lazyView(activity, 'AccountActivityPage'),
    'page.server.activity': lazyView(activity, 'ServerActivityPage'),

    // Shown in place of every server page; the server route loads it with the server layout.
    'server.conflict': lazyView(() => import('./server/ServerConflict')),
    'page.server.schedules': lazyView(schedules, 'SchedulesPage'),
    'server.schedules': lazyView(schedules, 'ScheduleList'),
    'page.server.schedules.detail': lazyView(schedules, 'ScheduleDetailPage'),
    'server.schedule': lazyView(schedules, 'ScheduleDetails'),
    'server.schedule.tasks': lazyView(schedules, 'ScheduleTasks'),
    'page.server.users': lazyView(users, 'UsersPage'),
    'server.subusers': lazyView(users, 'SubuserList'),
    'page.server.users.create': lazyView(users, 'CreateSubuserPage'),
    'server.subuserForm': lazyView(users, 'SubuserForm'),
    'page.server.backups': lazyView(backups, 'BackupsPage'),
    'server.backups': lazyView(backups, 'BackupList'),
    'page.server.settings': lazyView(settings, 'SettingsPage'),
    'server.sftp': lazyView(settings, 'ServerSftp'),
    'server.debug': lazyView(settings, 'ServerDebug'),
    'server.rename': lazyView(settings, 'ServerRename'),
    'server.reinstall': lazyView(settings, 'ServerReinstall'),

    'page.server.console': lazyView(serverConsole, 'ConsolePage'),
    'server.terminal': lazyView(serverConsole, 'Terminal'),
    'server.power': lazyView(serverConsole, 'Power'),
    'server.stats': lazyView(serverConsole, 'Stats'),
    'server.graphs': lazyView(serverConsole, 'Graphs'),
    'server.features': lazyView(serverConsole, 'Features'),
    'server.feature.eula': lazyView(serverConsole, 'EulaPrompt'),
    'server.feature.javaVersion': lazyView(serverConsole, 'JavaVersionPrompt'),
    'server.feature.gslToken': lazyView(serverConsole, 'GslTokenPrompt'),
    'server.feature.pidLimit': lazyView(serverConsole, 'PidLimitPrompt'),
    'server.feature.steamDiskSpace': lazyView(serverConsole, 'SteamDiskSpacePrompt'),
    'server.feature.hytaleOauth': lazyView(serverConsole, 'HytaleOauthPrompt'),
    'page.server.files': lazyView(files, 'FilesPage'),
    'server.files.manager': lazyView(files, 'FileManager'),
    'server.files.details': lazyView(files, 'FileDetails'),
    'page.server.files.editor': lazyView(files, 'EditorPage'),
    'server.files.editor': lazyView(files, 'FileEditor'),
    'page.server.databases': lazyView(databases, 'DatabasesPage'),
    'server.databases': lazyView(databases, 'Databases'),
    'page.server.network': lazyView(network, 'NetworkPage'),
    'server.allocations': lazyView(network, 'Allocations'),
    'page.server.startup': lazyView(startup, 'StartupPage'),
    'server.startup.command': lazyView(startup, 'StartupCommand'),
    'server.startup.image': lazyView(startup, 'StartupImage'),
    'server.startup.variables': lazyView(startup, 'StartupVariables'),
};
