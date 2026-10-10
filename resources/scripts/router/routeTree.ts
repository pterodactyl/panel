import type { QueryClient } from '@tanstack/react-query';
import type { AnyRoute } from '@tanstack/react-router';
import { createRootRouteWithContext, createRoute, lazyRouteComponent, redirect } from '@tanstack/react-router';
import { publishAreaNav, type AreaNavEntry } from '@/router/nav';
import {
    getExtensionScreens,
    prepareExtensions,
    resolveScreenPath,
    type ScreenArea,
    type ScreenParent,
    type SiteExtensionEntry,
} from '@/extensions/registry';
import { extensionScreenComponent } from '@/extensions/screen';
import { accountApiKeysQueryOptions } from '@/api/account/api-keys/queries';
import { accountActivityFacetsQueryOptions, accountActivityLogsQueryOptions } from '@/api/account/activity/queries';
import { currentUserQueryOptions } from '@/api/account/queries';
import { accountServersQueryOptions } from '@/api/account/servers/queries';
import { accountSshKeysQueryOptions } from '@/api/account/ssh-keys/queries';
import {
    adminUserListQueryParams,
    adminUserWithServersQueryOptions,
    adminUsersQueryOptions,
    allAdminUsersQueryOptions,
} from '@/api/admin/users/queries';
import { adminMountListQueryParams, adminMountQueryOptions, adminMountsQueryOptions } from '@/api/admin/mounts/queries';
import { adminEggQueryOptions, adminEggsQueryOptions, adminEggVariablesQueryOptions } from '@/api/admin/eggs/queries';
import {
    adminNodeAllocationsQueryOptions,
    adminNodeConfigurationQueryOptions,
    adminNodeListQueryParams,
    adminNodeQueryOptions,
    adminNodeServersQueryOptions,
    adminNodesQueryOptions,
    allAdminNodesQueryOptions,
} from '@/api/admin/nodes/queries';
import {
    adminServerDatabasesQueryOptions,
    adminServerListQueryParams,
    adminServerMountsQueryOptions,
    adminServerQueryOptions,
    adminServersQueryOptions,
} from '@/api/admin/servers/queries';
import { adminApiKeyListQueryParams, adminApiKeysQueryOptions } from '@/api/admin/api-keys/queries';
import { adminExtensionsQueryOptions } from '@/api/admin/extensions/queries';
import { adminTagListQueryParams, adminTagsQueryOptions } from '@/api/admin/tags/queries';
import { adminSettingsQueryOptions } from '@/api/admin/settings/queries';
import { adminActivityFacetsQueryOptions, adminActivityLogsQueryOptions } from '@/api/admin/activity/queries';
import { adminVersionQueryOptions } from '@/api/admin/version/queries';
import {
    adminLocationListQueryParams,
    adminLocationQueryOptions,
    adminLocationsQueryOptions,
    allAdminLocationsQueryOptions,
} from '@/api/admin/locations/queries';
import {
    adminDatabaseHostDatabasesQueryOptions,
    adminDatabaseHostListQueryParams,
    adminDatabaseHostQueryOptions,
    adminDatabaseHostsQueryOptions,
} from '@/api/admin/database-hosts/queries';
import { selectServerPermissions, serverQueryOptions } from '@/api/server/queries';
import { serverBackupsQueryOptions } from '@/api/server/backups/queries';
import { serverDatabasesQueryOptions } from '@/api/server/databases/queries';
import { serverAllocationsQueryOptions } from '@/api/server/network/queries';
import { serverStartupQueryOptions } from '@/api/server/startup/queries';
import { serverScheduleQueryOptions, serverSchedulesQueryOptions } from '@/api/server/schedules/queries';
import { serverSubusersQueryOptions } from '@/api/server/users/queries';
import { serverActivityFacetsQueryOptions, serverActivityLogsQueryOptions } from '@/api/server/activity/queries';
import { systemPermissionsQueryOptions } from '@/api/system/queries';
import { siteSettingsQueryOptions } from '@/api/settings/queries';
import { getBootstrapExtensions, hasBootstrapSession } from '@/bootstrap';
import { registerClassPrefixes } from '@/lib/cn';
import RootLayout from '@/router/layouts/RootLayout';
import AuthLayout from '@/router/layouts/AuthLayout';
import RootNotFound from '@/router/RootNotFound';
import { RouteAccessDenied } from '@/router/RouteError';
import { eggIdParam, idParam, scheduleIdParam } from '@/router/params';
import { parseRedirectSearch } from '@/router/redirect';
import { requireServerPermission, serverScreen } from '@/router/serverScreen';
import { slottedRouteComponent } from '@/router/routeSlots';
import {
    parseActivitySearch,
    parseAdminListSearch,
    parseApiKeyListSearch,
    parseDashboardSearch,
    parseDatabaseHostListSearch,
    parseEmailSearch,
    parseLocationListSearch,
    parseMountListSearch,
    parseNodeListSearch,
    parsePageSearch,
    parseServerListSearch,
    parseTagListSearch,
    parseUserListSearch,
} from '@/router/search';

type RouterContext = {
    queryClient: QueryClient;
};

const rootRoute = createRootRouteWithContext<RouterContext>()({
    loader: async ({ context }) => {
        await context.queryClient.ensureQueryData({ ...siteSettingsQueryOptions(), revalidateIfStale: true });
    },
    component: RootLayout,
    notFoundComponent: RootNotFound,
});

const authRoute = createRoute({ getParentRoute: () => rootRoute, path: 'auth', component: AuthLayout });

const authChildren = [
    createRoute({
        getParentRoute: () => authRoute,
        path: 'login',
        component: slottedRouteComponent(() => import('@/components/auth/LoginContainer'), {
            before: 'auth.login.before',
            after: 'auth.login.after',
        }),
        validateSearch: parseRedirectSearch,
    }),
    createRoute({
        getParentRoute: () => authRoute,
        path: 'login/checkpoint',
        component: slottedRouteComponent(() => import('@/components/auth/LoginCheckpointContainer'), {
            before: 'auth.checkpoint.before',
            after: 'auth.checkpoint.after',
        }),
        validateSearch: parseRedirectSearch,
    }),
    createRoute({
        getParentRoute: () => authRoute,
        path: 'password',
        component: slottedRouteComponent(() => import('@/components/auth/ForgotPasswordContainer'), {
            before: 'auth.password.before',
            after: 'auth.password.after',
        }),
    }),
    createRoute({
        getParentRoute: () => authRoute,
        path: 'password/reset/$token',
        component: slottedRouteComponent(() => import('@/components/auth/ResetPasswordContainer'), {
            before: 'auth.passwordReset.before',
            after: 'auth.passwordReset.after',
        }),
        validateSearch: parseEmailSearch,
    }),
];

const authenticatedRoute = createRoute({
    getParentRoute: () => rootRoute,
    id: 'authenticated',
    beforeLoad: ({ location }) => {
        if (!hasBootstrapSession()) {
            throw redirect({ to: '/auth/login', search: parseRedirectSearch({ redirect: location.href }) });
        }
    },
    loader: async ({ context }) => {
        await context.queryClient.ensureQueryData({ ...currentUserQueryOptions(), revalidateIfStale: true });
    },
    // Layouts behind the session are lazy so the login screen does not ship the navigation
    // bar, admin sidebar, or server console in the entry chunk.
    component: lazyRouteComponent(() => import('@/router/layouts/AuthenticatedLayout')),
});

const dashboardRoute = createRoute({
    getParentRoute: () => authenticatedRoute,
    path: '/',
    validateSearch: parseDashboardSearch,
    loaderDeps: ({ search }) => ({ page: search.page ?? 1, type: search.type }),
    loader: async ({ context, deps }) => {
        const user = await context.queryClient.ensureQueryData({
            ...currentUserQueryOptions(),
            revalidateIfStale: true,
        });

        await context.queryClient.ensureQueryData({
            ...accountServersQueryOptions({ page: deps.page, type: user.rootAdmin ? deps.type : undefined }),
            revalidateIfStale: true,
        });
    },
    component: lazyRouteComponent(() => import('@/components/dashboard/DashboardContainer')),
});

const accountRoute = createRoute({
    getParentRoute: () => authenticatedRoute,
    path: 'account',
    component: lazyRouteComponent(() => import('@/router/layouts/AccountLayout')),
});
const accountChildren = [
    createRoute({
        getParentRoute: () => accountRoute,
        path: '/',
        staticData: { nav: { label: 'Account', exact: true } },
        component: lazyRouteComponent(() => import('@/components/dashboard/AccountOverviewContainer')),
    }),
    createRoute({
        getParentRoute: () => accountRoute,
        path: 'api',
        staticData: { nav: { label: 'API Credentials' } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...accountApiKeysQueryOptions(), revalidateIfStale: true });
        },
        component: slottedRouteComponent(() => import('@/components/dashboard/AccountApiContainer'), {
            before: 'account.api.before',
            after: 'account.api.after',
        }),
    }),
    createRoute({
        getParentRoute: () => accountRoute,
        path: 'ssh',
        staticData: { nav: { label: 'SSH Keys' } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...accountSshKeysQueryOptions(), revalidateIfStale: true });
        },
        component: slottedRouteComponent(() => import('@/components/dashboard/ssh/AccountSSHContainer'), {
            before: 'account.ssh.before',
            after: 'account.ssh.after',
        }),
    }),
    createRoute({
        getParentRoute: () => accountRoute,
        path: 'activity',
        staticData: { nav: { label: 'Activity' } },
        validateSearch: parseActivitySearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, event: search.event, user: search.user }),
        loader: async ({ context, deps }) => {
            await Promise.all([
                context.queryClient.ensureQueryData({
                    ...accountActivityLogsQueryOptions({
                        page: deps.page,
                        filters: { event: deps.event, user: deps.user },
                        sorts: { timestamp: -1 },
                    }),
                    revalidateIfStale: true,
                }),
                context.queryClient.ensureQueryData({
                    ...accountActivityFacetsQueryOptions(),
                    revalidateIfStale: true,
                }),
            ]);
        },
        component: slottedRouteComponent(() => import('@/components/dashboard/activity/ActivityLogContainer'), {
            before: 'account.activity.before',
            after: 'account.activity.after',
        }),
    }),
];

const serverRoute = createRoute({
    getParentRoute: () => authenticatedRoute,
    path: 'server/$id',
    beforeLoad: async ({ context, params }) => {
        const server = await context.queryClient.ensureQueryData({
            ...serverQueryOptions(params.id),
            revalidateIfStale: true,
        });

        return { serverUuid: server.attributes.uuid, serverPermissions: selectServerPermissions(server) };
    },
    component: lazyRouteComponent(() => import('@/router/layouts/ServerLayout')),
});
const serverChildren = [
    // ServerLayout renders the console itself.
    createRoute({
        getParentRoute: () => serverRoute,
        path: '/',
        staticData: { nav: { label: 'Console', exact: true } },
        component: () => null,
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'files',
        staticData: { nav: { label: 'Files', permission: 'file.*' } },
        beforeLoad: requireServerPermission('file.*'),
        component: serverScreen(() => import('@/components/server/files/FileManagerContainer'), 'file.*'),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'files/$action',
        beforeLoad: requireServerPermission('file.*'),
        component: serverScreen(() => import('@/components/server/files/FileEditContainer'), 'file.*', {
            before: 'server.files.editor.before',
            after: 'server.files.editor.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'databases',
        staticData: { nav: { label: 'Databases', permission: 'database.*' } },
        beforeLoad: requireServerPermission('database.*'),
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({
                ...serverDatabasesQueryOptions(context.serverUuid),
                revalidateIfStale: true,
            });
        },
        component: serverScreen(() => import('@/components/server/databases/DatabasesContainer'), 'database.*', {
            before: 'server.databases.before',
            after: 'server.databases.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'schedules',
        staticData: { nav: { label: 'Schedules', permission: 'schedule.*' } },
        beforeLoad: requireServerPermission('schedule.*'),
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({
                ...serverSchedulesQueryOptions(context.serverUuid),
                revalidateIfStale: true,
            });
        },
        component: serverScreen(() => import('@/components/server/schedules/ScheduleContainer'), 'schedule.*', {
            before: 'server.schedules.before',
            after: 'server.schedules.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'schedules/$scheduleId',
        params: scheduleIdParam,
        beforeLoad: requireServerPermission('schedule.*'),
        loader: async ({ context, params }) => {
            await context.queryClient.ensureQueryData({
                ...serverScheduleQueryOptions(context.serverUuid, params.scheduleId),
                revalidateIfStale: true,
            });
        },
        component: serverScreen(() => import('@/components/server/schedules/ScheduleEditContainer'), 'schedule.*', {
            before: 'server.schedules.detail.before',
            after: 'server.schedules.detail.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'users/new',
        beforeLoad: requireServerPermission('user.create'),
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...systemPermissionsQueryOptions, revalidateIfStale: true });
        },
        component: serverScreen(() => import('@/components/server/users/CreateSubuserContainer'), 'user.create', {
            before: 'server.users.create.before',
            after: 'server.users.create.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'users',
        staticData: { nav: { label: 'Users', permission: 'user.*' } },
        beforeLoad: requireServerPermission('user.*'),
        loader: async ({ context }) => {
            await Promise.all([
                context.queryClient.ensureQueryData({ ...systemPermissionsQueryOptions, revalidateIfStale: true }),
                context.queryClient.ensureQueryData({
                    ...serverSubusersQueryOptions(context.serverUuid),
                    revalidateIfStale: true,
                }),
            ]);
        },
        component: serverScreen(() => import('@/components/server/users/UsersContainer'), 'user.*', {
            before: 'server.users.before',
            after: 'server.users.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'backups',
        staticData: { nav: { label: 'Backups', permission: 'backup.*' } },
        validateSearch: parsePageSearch,
        beforeLoad: requireServerPermission('backup.*'),
        loaderDeps: ({ search }) => ({ page: search.page ?? 1 }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...serverBackupsQueryOptions(context.serverUuid, deps.page),
                revalidateIfStale: true,
            });
        },
        component: serverScreen(() => import('@/components/server/backups/BackupContainer'), 'backup.*', {
            before: 'server.backups.before',
            after: 'server.backups.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'network',
        staticData: { nav: { label: 'Network', permission: 'allocation.*' } },
        beforeLoad: requireServerPermission('allocation.*'),
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({
                ...serverAllocationsQueryOptions(context.serverUuid),
                revalidateIfStale: true,
            });
        },
        component: serverScreen(() => import('@/components/server/network/NetworkContainer'), 'allocation.*', {
            before: 'server.network.before',
            after: 'server.network.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'startup',
        staticData: { nav: { label: 'Startup', permission: 'startup.*' } },
        beforeLoad: requireServerPermission('startup.*'),
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({
                ...serverStartupQueryOptions(context.serverUuid),
                revalidateIfStale: true,
            });
        },
        component: serverScreen(() => import('@/components/server/startup/StartupContainer'), 'startup.*', {
            before: 'server.startup.before',
            after: 'server.startup.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'settings',
        staticData: { nav: { label: 'Settings', permission: ['settings.*', 'file.sftp'] } },
        beforeLoad: requireServerPermission(['settings.*', 'file.sftp']),
        component: serverScreen(
            () => import('@/components/server/settings/SettingsContainer'),
            ['settings.*', 'file.sftp'],
            { before: 'server.settings.before', after: 'server.settings.after' }
        ),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'activity',
        staticData: { nav: { label: 'Activity', permission: 'activity.*' } },
        validateSearch: parseActivitySearch,
        beforeLoad: requireServerPermission('activity.*'),
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, event: search.event, user: search.user }),
        loader: async ({ context, deps }) => {
            await Promise.all([
                context.queryClient.ensureQueryData({
                    ...serverActivityLogsQueryOptions(context.serverUuid, {
                        page: deps.page,
                        filters: { event: deps.event, user: deps.user },
                        sorts: { timestamp: -1 },
                    }),
                    revalidateIfStale: true,
                }),
                context.queryClient.ensureQueryData({
                    ...serverActivityFacetsQueryOptions(context.serverUuid),
                    revalidateIfStale: true,
                }),
            ]);
        },
        component: serverScreen(() => import('@/components/server/ServerActivityLogContainer'), 'activity.*', {
            before: 'server.activity.before',
            after: 'server.activity.after',
        }),
    }),
];

const panelRoute = createRoute({
    getParentRoute: () => authenticatedRoute,
    path: 'panel',
    beforeLoad: async ({ context }) => {
        const user = await context.queryClient.ensureQueryData(currentUserQueryOptions());

        if (!user.rootAdmin) {
            throw new RouteAccessDenied('You do not have permission to access the administrative area.');
        }
    },
    component: lazyRouteComponent(() => import('@/router/layouts/AdminLayout')),
});
const adminPage = slottedRouteComponent;

const nodeDetailImport = () => import('@/components/admin/nodes/NodeDetailContainer');

export const nodeDetailRoute = createRoute({
    getParentRoute: () => panelRoute,
    path: 'nodes/$id',
    params: idParam,
    loader: async ({ context, params }) =>
        context.queryClient.ensureQueryData({
            ...adminNodeQueryOptions(params.id),
            revalidateIfStale: true,
        }),
    component: adminPage(nodeDetailImport, {
        before: 'panel.nodes.detail.before',
        after: 'panel.nodes.detail.after',
    }),
});
const nodeDetailChildren = [
    createRoute({
        getParentRoute: () => nodeDetailRoute,
        path: '/',
        component: adminPage(() => import('@/components/admin/nodes/NodeAboutTab'), {
            before: 'panel.nodes.detail.about.before',
            after: 'panel.nodes.detail.about.after',
        }),
    }),
    createRoute({
        getParentRoute: () => nodeDetailRoute,
        path: 'settings',
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({
                ...allAdminLocationsQueryOptions(),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/nodes/NodeSettingsTab'), {
            before: 'panel.nodes.detail.settings.before',
            after: 'panel.nodes.detail.settings.after',
        }),
    }),
    createRoute({
        getParentRoute: () => nodeDetailRoute,
        path: 'configuration',
        loader: async ({ context, params }) => {
            await context.queryClient.ensureQueryData({
                ...adminNodeConfigurationQueryOptions(params.id),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/nodes/NodeConfigurationTab'), {
            before: 'panel.nodes.detail.configuration.before',
            after: 'panel.nodes.detail.configuration.after',
        }),
    }),
    createRoute({
        getParentRoute: () => nodeDetailRoute,
        path: 'allocation',
        validateSearch: parseAdminListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1 }),
        loader: async ({ context, deps, params }) => {
            await context.queryClient.ensureQueryData({
                ...adminNodeAllocationsQueryOptions(params.id, deps.page),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/nodes/NodeAllocationTab'), {
            before: 'panel.nodes.detail.allocations.before',
            after: 'panel.nodes.detail.allocations.after',
        }),
    }),
    createRoute({
        getParentRoute: () => nodeDetailRoute,
        path: 'servers',
        validateSearch: parseAdminListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1 }),
        loader: async ({ context, deps, params }) => {
            await context.queryClient.ensureQueryData({
                ...adminNodeServersQueryOptions(params.id, deps.page),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/nodes/NodeServersTab'), {
            before: 'panel.nodes.detail.servers.before',
            after: 'panel.nodes.detail.servers.after',
        }),
    }),
];

const serverDetailImport = () => import('@/components/admin/servers/ServerDetailContainer');

export const serverDetailRoute = createRoute({
    getParentRoute: () => panelRoute,
    path: 'servers/$id',
    params: idParam,
    loader: async ({ context, params }) =>
        context.queryClient.ensureQueryData({
            ...adminServerQueryOptions(params.id),
            revalidateIfStale: true,
        }),
    component: adminPage(serverDetailImport, {
        before: 'panel.servers.detail.before',
        after: 'panel.servers.detail.after',
    }),
});
const serverDetailChildren = [
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: '/',
        component: adminPage(() => import('@/components/admin/servers/ServerAboutTab'), {
            before: 'panel.servers.detail.about.before',
            after: 'panel.servers.detail.about.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: 'details',
        component: adminPage(() => import('@/components/admin/servers/ServerDetailsTab'), {
            before: 'panel.servers.detail.details.before',
            after: 'panel.servers.detail.details.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: 'build',
        component: adminPage(() => import('@/components/admin/servers/ServerBuildTab'), {
            before: 'panel.servers.detail.build.before',
            after: 'panel.servers.detail.build.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: 'startup',
        component: adminPage(() => import('@/components/admin/servers/ServerStartupTab'), {
            before: 'panel.servers.detail.startup.before',
            after: 'panel.servers.detail.startup.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: 'database',
        loader: async ({ context, params }) => {
            const server = await context.queryClient.ensureQueryData({
                ...adminServerQueryOptions(params.id),
                revalidateIfStale: true,
            });

            if (server.attributes.container.installed === 1) {
                await context.queryClient.ensureQueryData({
                    ...adminServerDatabasesQueryOptions(params.id),
                    revalidateIfStale: true,
                });
            }
        },
        component: adminPage(() => import('@/components/admin/servers/ServerDatabaseTab'), {
            before: 'panel.servers.detail.databases.before',
            after: 'panel.servers.detail.databases.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: 'mounts',
        loader: async ({ context, params }) => {
            const server = await context.queryClient.ensureQueryData({
                ...adminServerQueryOptions(params.id),
                revalidateIfStale: true,
            });

            if (server.attributes.container.installed === 1) {
                await context.queryClient.ensureQueryData({
                    ...adminServerMountsQueryOptions(params.id),
                    revalidateIfStale: true,
                });
            }
        },
        component: adminPage(() => import('@/components/admin/servers/ServerMountsTab'), {
            before: 'panel.servers.detail.mounts.before',
            after: 'panel.servers.detail.mounts.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: 'manage',
        component: adminPage(() => import('@/components/admin/servers/ServerManageTab'), {
            before: 'panel.servers.detail.manage.before',
            after: 'panel.servers.detail.manage.after',
        }),
    }),
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: 'delete',
        component: adminPage(() => import('@/components/admin/servers/ServerDeleteTab'), {
            before: 'panel.servers.detail.delete.before',
            after: 'panel.servers.detail.delete.after',
        }),
    }),
];

const eggDetailImport = () => import('@/components/admin/eggs/EggDetailContainer');

export const eggDetailRoute = createRoute({
    getParentRoute: () => panelRoute,
    path: 'eggs/$eggId',
    params: eggIdParam,
    loader: async ({ context, params }) =>
        context.queryClient.ensureQueryData({ ...adminEggQueryOptions(params.eggId), revalidateIfStale: true }),
    component: adminPage(eggDetailImport, {
        before: 'panel.eggs.detail.before',
        after: 'panel.eggs.detail.after',
    }),
});
const eggDetailChildren = [
    createRoute({
        getParentRoute: () => eggDetailRoute,
        path: '/',
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminEggsQueryOptions(), revalidateIfStale: true });
        },
        component: adminPage(() => import('@/components/admin/eggs/EggConfigurationTab'), {
            before: 'panel.eggs.detail.configuration.before',
            after: 'panel.eggs.detail.configuration.after',
        }),
    }),
    createRoute({
        getParentRoute: () => eggDetailRoute,
        path: 'tags',
        component: adminPage(() => import('@/components/admin/eggs/EggTagsTab'), {
            before: 'panel.eggs.detail.tags.before',
            after: 'panel.eggs.detail.tags.after',
        }),
    }),
    createRoute({
        getParentRoute: () => eggDetailRoute,
        path: 'variables',
        loader: async ({ context, params }) => {
            await context.queryClient.ensureQueryData({
                ...adminEggVariablesQueryOptions(params.eggId),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/eggs/EggVariablesTab'), {
            before: 'panel.eggs.detail.variables.before',
            after: 'panel.eggs.detail.variables.after',
        }),
    }),
    createRoute({
        getParentRoute: () => eggDetailRoute,
        path: 'script',
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminEggsQueryOptions(), revalidateIfStale: true });
        },
        component: adminPage(() => import('@/components/admin/eggs/EggScriptTab'), {
            before: 'panel.eggs.detail.script.before',
            after: 'panel.eggs.detail.script.after',
        }),
    }),
];

export const mountDetailRoute = createRoute({
    getParentRoute: () => panelRoute,
    path: 'mounts/$id',
    params: idParam,
    loader: async ({ context, params }) =>
        context.queryClient.ensureQueryData({
            ...adminMountQueryOptions(params.id),
            revalidateIfStale: true,
        }),
    component: adminPage(() => import('@/components/admin/mounts/MountDetailContainer'), {
        before: 'panel.mounts.detail.before',
        after: 'panel.mounts.detail.after',
    }),
});

export const userDetailRoute = createRoute({
    getParentRoute: () => panelRoute,
    path: 'users/$id',
    params: idParam,
    loader: async ({ context, params }) =>
        context.queryClient.ensureQueryData({
            ...adminUserWithServersQueryOptions(params.id),
            revalidateIfStale: true,
        }),
    component: adminPage(
        () =>
            import('@/components/admin/users/UserDetailContainer').then((module) => ({
                default: module.UserDetailLayout,
            })),
        {
            before: 'panel.users.detail.before',
            after: 'panel.users.detail.after',
        }
    ),
});

const userDetailChildren = [
    createRoute({
        getParentRoute: () => userDetailRoute,
        path: '/',
        component: lazyRouteComponent(() => import('@/components/admin/users/UserDetailContainer')),
    }),
];

export const locationDetailRoute = createRoute({
    getParentRoute: () => panelRoute,
    path: 'locations/$id',
    params: idParam,
    loader: async ({ context, params }) =>
        context.queryClient.ensureQueryData({
            ...adminLocationQueryOptions(params.id),
            revalidateIfStale: true,
        }),
    component: adminPage(() => import('@/components/admin/locations/LocationDetailContainer'), {
        before: 'panel.locations.detail.before',
        after: 'panel.locations.detail.after',
    }),
});

export const databaseHostDetailRoute = createRoute({
    getParentRoute: () => panelRoute,
    path: 'databases/$id',
    params: idParam,
    validateSearch: parsePageSearch,
    loaderDeps: ({ search }) => ({ page: search.page ?? 1 }),
    loader: async ({ context, deps, params }) => {
        const [host] = await Promise.all([
            context.queryClient.ensureQueryData({
                ...adminDatabaseHostQueryOptions(params.id),
                revalidateIfStale: true,
            }),
            context.queryClient.ensureQueryData({
                ...adminDatabaseHostDatabasesQueryOptions(params.id, deps.page),
                revalidateIfStale: true,
            }),
            context.queryClient.ensureQueryData({ ...allAdminNodesQueryOptions(), revalidateIfStale: true }),
            context.queryClient.ensureQueryData({
                ...allAdminLocationsQueryOptions(),
                revalidateIfStale: true,
            }),
        ]);

        return host;
    },
    component: adminPage(() => import('@/components/admin/databases/DatabaseHostDetailContainer'), {
        before: 'panel.databaseHosts.detail.before',
        after: 'panel.databaseHosts.detail.after',
    }),
});

const panelChildren = [
    createRoute({
        getParentRoute: () => panelRoute,
        path: '/',
        staticData: { nav: { label: 'Overview', exact: true } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminVersionQueryOptions(), revalidateIfStale: true });
        },
        component: lazyRouteComponent(() => import('@/components/admin/overview/OverviewContainer')),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'activity',
        staticData: { nav: { label: 'Activity' } },
        validateSearch: parseActivitySearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, event: search.event, user: search.user }),
        loader: async ({ context, deps }) => {
            await Promise.all([
                context.queryClient.ensureQueryData({
                    ...adminActivityLogsQueryOptions({
                        page: deps.page,
                        filters: { event: deps.event, user: deps.user },
                        sorts: { timestamp: -1 },
                    }),
                    revalidateIfStale: true,
                }),
                context.queryClient.ensureQueryData({ ...adminActivityFacetsQueryOptions(), revalidateIfStale: true }),
            ]);
        },
        component: adminPage(() => import('@/components/admin/activity/ActivityContainer'), {
            before: 'panel.activity.before',
            after: 'panel.activity.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'settings',
        staticData: { nav: { label: 'Settings' } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminSettingsQueryOptions(), revalidateIfStale: true });
        },
        component: adminPage(() => import('@/components/admin/settings/SettingsContainer'), {
            before: 'panel.settings.before',
            after: 'panel.settings.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'settings/mail',
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminSettingsQueryOptions(), revalidateIfStale: true });
        },
        component: adminPage(() => import('@/components/admin/settings/SettingsContainer'), {
            before: 'panel.settings.before',
            after: 'panel.settings.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'settings/advanced',
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminSettingsQueryOptions(), revalidateIfStale: true });
        },
        component: adminPage(() => import('@/components/admin/settings/SettingsContainer'), {
            before: 'panel.settings.before',
            after: 'panel.settings.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'settings/logo',
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminSettingsQueryOptions(), revalidateIfStale: true });
        },
        component: adminPage(() => import('@/components/admin/settings/SettingsContainer'), {
            before: 'panel.settings.before',
            after: 'panel.settings.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'users',
        staticData: { nav: { label: 'Users' } },
        validateSearch: parseUserListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminUsersQueryOptions(adminUserListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/users/UsersContainer'), {
            before: 'panel.users.before',
            after: 'panel.users.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'users/new',
        component: adminPage(() => import('@/components/admin/users/CreateUserForm'), {
            before: 'panel.users.create.before',
            after: 'panel.users.create.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'locations',
        staticData: { nav: { label: 'Locations' } },
        validateSearch: parseLocationListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminLocationsQueryOptions(adminLocationListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/locations/LocationsContainer'), {
            before: 'panel.locations.before',
            after: 'panel.locations.after',
        }),
    }),
    locationDetailRoute,
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'nodes',
        staticData: { nav: { label: 'Nodes' } },
        validateSearch: parseNodeListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminNodesQueryOptions(adminNodeListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/nodes/NodesContainer'), {
            before: 'panel.nodes.before',
            after: 'panel.nodes.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'nodes/new',
        component: adminPage(() => import('@/components/admin/nodes/CreateNodeForm'), {
            before: 'panel.nodes.create.before',
            after: 'panel.nodes.create.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'servers',
        staticData: { nav: { label: 'Servers' } },
        validateSearch: parseServerListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminServersQueryOptions(adminServerListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/servers/ServersContainer'), {
            before: 'panel.servers.before',
            after: 'panel.servers.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'servers/new',
        loader: async ({ context }) => {
            await Promise.all([
                context.queryClient.ensureQueryData({
                    ...allAdminUsersQueryOptions(),
                    revalidateIfStale: true,
                }),
                context.queryClient.ensureQueryData({
                    ...allAdminNodesQueryOptions(),
                    revalidateIfStale: true,
                }),
                context.queryClient.ensureQueryData({
                    ...allAdminLocationsQueryOptions(),
                    revalidateIfStale: true,
                }),
                context.queryClient.ensureQueryData({ ...adminEggsQueryOptions(), revalidateIfStale: true }),
            ]);
        },
        component: adminPage(() => import('@/components/admin/servers/CreateServerForm'), {
            before: 'panel.servers.create.before',
            after: 'panel.servers.create.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'databases',
        staticData: { nav: { label: 'Database Hosts' } },
        validateSearch: parseDatabaseHostListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminDatabaseHostsQueryOptions(adminDatabaseHostListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/databases/DatabaseHostsContainer'), {
            before: 'panel.databaseHosts.before',
            after: 'panel.databaseHosts.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'databases/new',
        component: adminPage(() => import('@/components/admin/databases/CreateDatabaseHostForm'), {
            before: 'panel.databaseHosts.create.before',
            after: 'panel.databaseHosts.create.after',
        }),
    }),
    databaseHostDetailRoute,
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'mounts',
        staticData: { nav: { label: 'Mounts' } },
        validateSearch: parseMountListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminMountsQueryOptions(adminMountListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/mounts/MountsContainer'), {
            before: 'panel.mounts.before',
            after: 'panel.mounts.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'mounts/new',
        component: adminPage(() => import('@/components/admin/mounts/CreateMountForm'), {
            before: 'panel.mounts.create.before',
            after: 'panel.mounts.create.after',
        }),
    }),
    mountDetailRoute,
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'eggs',
        staticData: { nav: { label: 'Eggs' } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminEggsQueryOptions(), revalidateIfStale: true });
        },
        component: adminPage(() => import('@/components/admin/eggs/EggsContainer'), {
            before: 'panel.eggs.before',
            after: 'panel.eggs.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'eggs/new',
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminEggsQueryOptions(), revalidateIfStale: true });
        },
        component: adminPage(() => import('@/components/admin/eggs/CreateEggForm'), {
            before: 'panel.eggs.create.before',
            after: 'panel.eggs.create.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'eggs/catalog',
        component: lazyRouteComponent(() => import('@/components/admin/eggs/EggCatalogContainer')),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'tags',
        staticData: { nav: { label: 'Tags' } },
        validateSearch: parseTagListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminTagsQueryOptions(adminTagListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/tags/TagsContainer'), {
            before: 'panel.tags.before',
            after: 'panel.tags.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'extensions',
        staticData: { nav: { label: 'Extensions' } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminExtensionsQueryOptions(), revalidateIfStale: true });
        },
        component: adminPage(() => import('@/components/admin/extensions/ExtensionsContainer'), {
            before: 'panel.extensions.before',
            after: 'panel.extensions.after',
        }),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'api',
        staticData: { nav: { label: 'API Keys' } },
        validateSearch: parseApiKeyListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminApiKeysQueryOptions(adminApiKeyListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: adminPage(() => import('@/components/admin/api/ApiKeysContainer'), {
            before: 'panel.apiKeys.before',
            after: 'panel.apiKeys.after',
        }),
    }),
];

const panelResourceRoutes = [nodeDetailRoute, serverDetailRoute, eggDetailRoute, userDetailRoute];

const declaredPath = (route: AnyRoute): string => ('path' in route.options ? route.options.path : undefined) ?? '';

const extensionRoutes = (parent: AnyRoute, area: ScreenArea, resourceParent?: ScreenParent): AnyRoute[] =>
    getExtensionScreens(area, resourceParent).map((screen) =>
        createRoute({
            getParentRoute: () => parent,
            path: screen.path,
            staticData: screen.nav ? { nav: { ...screen.nav, permission: screen.permission, screen } } : {},
            component: extensionScreenComponent(screen, area),
        })
    );

function withExtensionRoutes<TRoutes extends readonly AnyRoute[]>(
    routes: TRoutes,
    parent: AnyRoute,
    area: ScreenArea,
    resourceParent?: ScreenParent
): TRoutes;
function withExtensionRoutes(
    routes: readonly AnyRoute[],
    parent: AnyRoute,
    area: ScreenArea,
    resourceParent?: ScreenParent
): readonly AnyRoute[] {
    return [...routes, ...extensionRoutes(parent, area, resourceParent)];
}

const navEntries = (routes: readonly AnyRoute[]): AreaNavEntry[] =>
    routes.flatMap((route) => {
        const nav = route.options.staticData?.nav;
        const path = declaredPath(route);

        return nav ? [{ ...nav, segment: path === '/' ? '' : resolveScreenPath(path, nav.params) }] : [];
    });

/** Also publishes the area navigation. */
export function buildRouteTree(extensions: readonly SiteExtensionEntry[] = getBootstrapExtensions()) {
    registerClassPrefixes(extensions.map((entry) => entry.prefix));
    prepareExtensions(
        extensions,
        {
            account: accountChildren.map(declaredPath),
            server: serverChildren.map(declaredPath),
            admin: [...panelChildren, ...panelResourceRoutes].map(declaredPath),
        },
        {
            'admin.node': nodeDetailChildren.map(declaredPath),
            'admin.server': serverDetailChildren.map(declaredPath),
            'admin.egg': eggDetailChildren.map(declaredPath),
            'admin.user': userDetailChildren.map(declaredPath),
        }
    );

    const accountRouteChildren = withExtensionRoutes(accountChildren, accountRoute, 'account');
    const serverRouteChildren = withExtensionRoutes(serverChildren, serverRoute, 'server');
    const panelRouteChildren = withExtensionRoutes(
        [
            ...panelChildren,
            nodeDetailRoute.addChildren(
                withExtensionRoutes(nodeDetailChildren, nodeDetailRoute, 'admin', 'admin.node')
            ),
            serverDetailRoute.addChildren(
                withExtensionRoutes(serverDetailChildren, serverDetailRoute, 'admin', 'admin.server')
            ),
            eggDetailRoute.addChildren(withExtensionRoutes(eggDetailChildren, eggDetailRoute, 'admin', 'admin.egg')),
            userDetailRoute.addChildren(
                withExtensionRoutes(userDetailChildren, userDetailRoute, 'admin', 'admin.user')
            ),
        ],
        panelRoute,
        'admin'
    );

    publishAreaNav('account', navEntries(accountRouteChildren));
    publishAreaNav('server', navEntries(serverRouteChildren));
    publishAreaNav('admin', navEntries(panelRouteChildren));

    return rootRoute.addChildren([
        authRoute.addChildren(authChildren),
        authenticatedRoute.addChildren([
            dashboardRoute,
            accountRoute.addChildren(accountRouteChildren),
            serverRoute.addChildren(serverRouteChildren),
            panelRoute.addChildren(panelRouteChildren),
        ]),
    ]);
}
