import { lazyView, type ThemeViews } from '@/sdk/theme';
import AuthLayout from './layout/AuthLayout';

const dashboard = () => import('./dashboard');
const auth = () => import('./auth');
const account = () => import('./account');
const activity = () => import('./activity');

/** Classic's own views; the rest still render the existing containers as their default. */
export const classicViews: ThemeViews = {
    // The sign-in screen renders without waiting for a chunk.
    'layout.auth': AuthLayout,
    'layout.app': lazyView(() => import('./layout/AppLayout')),
    'layout.account': lazyView(() => import('./layout/AccountLayout')),
    'layout.server': lazyView(() => import('./layout/ServerLayout')),
    'layout.admin': lazyView(() => import('./layout/AdminLayout')),
    'page.dashboard': lazyView(dashboard, 'Dashboard'),
    'dashboard.serverList': lazyView(dashboard, 'ServerList'),
    'dashboard.serverCard': lazyView(dashboard, 'ServerCard'),

    'page.auth.login': lazyView(auth, 'LoginPage'),
    'page.auth.checkpoint': lazyView(auth, 'CheckpointPage'),
    'page.auth.password': lazyView(auth, 'ForgotPasswordPage'),
    'page.auth.passwordReset': lazyView(auth, 'ResetPasswordPage'),
    'auth.loginForm': lazyView(auth, 'LoginForm'),
    'auth.checkpointForm': lazyView(auth, 'CheckpointForm'),
    'auth.forgotPasswordForm': lazyView(auth, 'ForgotPasswordForm'),
    'auth.resetPasswordForm': lazyView(auth, 'ResetPasswordForm'),

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
};
