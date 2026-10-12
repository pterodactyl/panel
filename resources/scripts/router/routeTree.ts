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
    adminNodeTagsQueryOptions,
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
import { adminThemesQueryOptions } from '@/api/admin/themes/queries';
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
import RootNotFound from '@/router/RootNotFound';
import { RouteAccessDenied } from '@/router/RouteError';
import { eggIdParam, idParam, scheduleIdParam } from '@/router/params';
import { parseRedirectSearch } from '@/router/redirect';
import { bareAreaPageRoute, layoutRoute, pageRoute, requireServerPermission } from '@/router/viewRoutes';
import { preloadView } from '@/views/View';
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
    parseWorkbenchSearch,
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

const authRoute = createRoute({
    getParentRoute: () => rootRoute,
    path: 'auth',
    component: layoutRoute('layout.auth', 'auth'),
});

const authChildren = [
    createRoute({
        getParentRoute: () => authRoute,
        path: 'login',
        component: pageRoute('page.auth.login'),
        validateSearch: parseRedirectSearch,
    }),
    createRoute({
        getParentRoute: () => authRoute,
        path: 'login/checkpoint',
        component: pageRoute('page.auth.checkpoint'),
        validateSearch: parseRedirectSearch,
    }),
    createRoute({
        getParentRoute: () => authRoute,
        path: 'password',
        component: pageRoute('page.auth.password'),
    }),
    createRoute({
        getParentRoute: () => authRoute,
        path: 'password/reset/$token',
        component: pageRoute('page.auth.passwordReset'),
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
    component: layoutRoute('layout.app', 'app'),
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
    component: pageRoute('page.dashboard'),
});

const accountRoute = createRoute({
    getParentRoute: () => authenticatedRoute,
    path: 'account',
    component: layoutRoute('layout.account', 'account'),
});
const accountChildren = [
    createRoute({
        getParentRoute: () => accountRoute,
        path: '/',
        staticData: { nav: { label: 'Account', exact: true } },
        component: pageRoute('page.account.overview'),
    }),
    createRoute({
        getParentRoute: () => accountRoute,
        path: 'api',
        staticData: { nav: { label: 'API Credentials' } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...accountApiKeysQueryOptions(), revalidateIfStale: true });
        },
        component: pageRoute('page.account.api'),
    }),
    createRoute({
        getParentRoute: () => accountRoute,
        path: 'ssh',
        staticData: { nav: { label: 'SSH Keys' } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...accountSshKeysQueryOptions(), revalidateIfStale: true });
        },
        component: pageRoute('page.account.ssh'),
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
        component: pageRoute('page.account.activity'),
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
    component: lazyRouteComponent(
        async () => (await Promise.all([import('@/router/layouts/ServerLayout'), preloadView('layout.server')]))[0]
    ),
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
        staticData: { nav: { label: 'Files', permission: 'file.*', hash: '/' } },
        beforeLoad: requireServerPermission('file.*'),
        component: pageRoute('page.server.files', 'file.*'),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'files/$action',
        beforeLoad: requireServerPermission('file.*'),
        component: pageRoute('page.server.files.editor', 'file.*'),
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
        component: pageRoute('page.server.databases', 'database.*'),
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
        component: pageRoute('page.server.schedules', 'schedule.*'),
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
        component: pageRoute('page.server.schedules.detail', 'schedule.*'),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'users/new',
        beforeLoad: requireServerPermission('user.create'),
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...systemPermissionsQueryOptions, revalidateIfStale: true });
        },
        component: pageRoute('page.server.users.create', 'user.create'),
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
        component: pageRoute('page.server.users', 'user.*'),
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
        component: pageRoute('page.server.backups', 'backup.*'),
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
        component: pageRoute('page.server.network', 'allocation.*'),
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
        component: pageRoute('page.server.startup', 'startup.*'),
    }),
    createRoute({
        getParentRoute: () => serverRoute,
        path: 'settings',
        staticData: { nav: { label: 'Settings', permission: ['settings.*', 'file.sftp'] } },
        beforeLoad: requireServerPermission(['settings.*', 'file.sftp']),
        component: pageRoute('page.server.settings', ['settings.*', 'file.sftp']),
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
        component: pageRoute('page.server.activity', 'activity.*'),
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
    component: layoutRoute('layout.admin', 'admin'),
});

/** Full width beside the admin area rather than inside its layout. */
const themeWorkbenchRoute = createRoute({
    getParentRoute: () => authenticatedRoute,
    path: 'panel/theme-workbench',
    validateSearch: parseWorkbenchSearch,
    beforeLoad: async ({ context }) => {
        const user = await context.queryClient.ensureQueryData(currentUserQueryOptions());

        if (!user.rootAdmin) {
            throw new RouteAccessDenied('You do not have permission to access the administrative area.');
        }
    },
    component: bareAreaPageRoute('page.admin.themeWorkbench', 'admin'),
});

export const nodeDetailRoute = createRoute({
    getParentRoute: () => panelRoute,
    path: 'nodes/$id',
    params: idParam,
    loader: async ({ context, params }) =>
        context.queryClient.ensureQueryData({
            ...adminNodeQueryOptions(params.id),
            revalidateIfStale: true,
        }),
    component: pageRoute('page.admin.nodes.detail'),
});
const nodeDetailChildren = [
    createRoute({
        getParentRoute: () => nodeDetailRoute,
        path: '/',
        component: pageRoute('page.admin.nodes.detail.about'),
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
        component: pageRoute('page.admin.nodes.detail.settings'),
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
        component: pageRoute('page.admin.nodes.detail.configuration'),
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
        component: pageRoute('page.admin.nodes.detail.allocations'),
    }),
    createRoute({
        getParentRoute: () => nodeDetailRoute,
        path: 'tags',
        loader: async ({ context, params }) => {
            await context.queryClient.ensureQueryData({
                ...adminNodeTagsQueryOptions(params.id),
                revalidateIfStale: true,
            });
        },
        component: pageRoute('page.admin.nodes.detail.tags'),
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
        component: pageRoute('page.admin.nodes.detail.servers'),
    }),
];

export const serverDetailRoute = createRoute({
    getParentRoute: () => panelRoute,
    path: 'servers/$id',
    params: idParam,
    loader: async ({ context, params }) =>
        context.queryClient.ensureQueryData({
            ...adminServerQueryOptions(params.id),
            revalidateIfStale: true,
        }),
    component: pageRoute('page.admin.servers.detail'),
});
const serverDetailChildren = [
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: '/',
        component: pageRoute('page.admin.servers.detail.about'),
    }),
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: 'details',
        component: pageRoute('page.admin.servers.detail.details'),
    }),
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: 'build',
        component: pageRoute('page.admin.servers.detail.build'),
    }),
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: 'startup',
        component: pageRoute('page.admin.servers.detail.startup'),
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
        component: pageRoute('page.admin.servers.detail.databases'),
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
        component: pageRoute('page.admin.servers.detail.mounts'),
    }),
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: 'manage',
        component: pageRoute('page.admin.servers.detail.manage'),
    }),
    createRoute({
        getParentRoute: () => serverDetailRoute,
        path: 'delete',
        component: pageRoute('page.admin.servers.detail.delete'),
    }),
];

export const eggDetailRoute = createRoute({
    getParentRoute: () => panelRoute,
    path: 'eggs/$eggId',
    params: eggIdParam,
    loader: async ({ context, params }) =>
        context.queryClient.ensureQueryData({ ...adminEggQueryOptions(params.eggId), revalidateIfStale: true }),
    component: pageRoute('page.admin.eggs.detail'),
});
const eggDetailChildren = [
    createRoute({
        getParentRoute: () => eggDetailRoute,
        path: '/',
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminEggsQueryOptions(), revalidateIfStale: true });
        },
        component: pageRoute('page.admin.eggs.detail.configuration'),
    }),
    createRoute({
        getParentRoute: () => eggDetailRoute,
        path: 'tags',
        component: pageRoute('page.admin.eggs.detail.tags'),
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
        component: pageRoute('page.admin.eggs.detail.variables'),
    }),
    createRoute({
        getParentRoute: () => eggDetailRoute,
        path: 'script',
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminEggsQueryOptions(), revalidateIfStale: true });
        },
        component: pageRoute('page.admin.eggs.detail.script'),
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
    component: pageRoute('page.admin.mounts.detail'),
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
    component: pageRoute('page.admin.users.detail'),
});

const userDetailChildren = [
    createRoute({
        getParentRoute: () => userDetailRoute,
        path: '/',
        component: pageRoute('page.admin.users.detail.about'),
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
    component: pageRoute('page.admin.locations.detail'),
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
    component: pageRoute('page.admin.databaseHosts.detail'),
});

const panelChildren = [
    createRoute({
        getParentRoute: () => panelRoute,
        path: '/',
        staticData: { nav: { label: 'Overview', exact: true, icon: 'layout-dashboard' } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminVersionQueryOptions(), revalidateIfStale: true });
        },
        component: pageRoute('page.admin.overview'),
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
        component: pageRoute('page.admin.activity'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'settings',
        staticData: { nav: { label: 'Settings', icon: 'settings' } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminSettingsQueryOptions(), revalidateIfStale: true });
        },
        component: pageRoute('page.admin.settings'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'settings/mail',
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminSettingsQueryOptions(), revalidateIfStale: true });
        },
        component: pageRoute('page.admin.settings.mail'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'settings/advanced',
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminSettingsQueryOptions(), revalidateIfStale: true });
        },
        component: pageRoute('page.admin.settings.advanced'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'users',
        staticData: { nav: { label: 'Users', icon: 'users' } },
        validateSearch: parseUserListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminUsersQueryOptions(adminUserListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: pageRoute('page.admin.users'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'users/new',
        component: pageRoute('page.admin.users.create'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'locations',
        staticData: { nav: { label: 'Locations', icon: 'map-pin' } },
        validateSearch: parseLocationListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminLocationsQueryOptions(adminLocationListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: pageRoute('page.admin.locations'),
    }),
    locationDetailRoute,
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'nodes',
        staticData: { nav: { label: 'Nodes', icon: 'network' } },
        validateSearch: parseNodeListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminNodesQueryOptions(adminNodeListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: pageRoute('page.admin.nodes'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'nodes/new',
        component: pageRoute('page.admin.nodes.create'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'servers',
        staticData: { nav: { label: 'Servers', icon: 'server' } },
        validateSearch: parseServerListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminServersQueryOptions(adminServerListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: pageRoute('page.admin.servers'),
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
        component: pageRoute('page.admin.servers.create'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'databases',
        staticData: { nav: { label: 'Database Hosts', icon: 'database' } },
        validateSearch: parseDatabaseHostListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminDatabaseHostsQueryOptions(adminDatabaseHostListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: pageRoute('page.admin.databaseHosts'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'databases/new',
        component: pageRoute('page.admin.databaseHosts.create'),
    }),
    databaseHostDetailRoute,
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'mounts',
        staticData: { nav: { label: 'Mounts', icon: 'folder-tree' } },
        validateSearch: parseMountListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminMountsQueryOptions(adminMountListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: pageRoute('page.admin.mounts'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'mounts/new',
        component: pageRoute('page.admin.mounts.create'),
    }),
    mountDetailRoute,
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'eggs',
        staticData: { nav: { label: 'Eggs', icon: 'egg' } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminEggsQueryOptions(), revalidateIfStale: true });
        },
        component: pageRoute('page.admin.eggs'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'eggs/new',
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminEggsQueryOptions(), revalidateIfStale: true });
        },
        component: pageRoute('page.admin.eggs.create'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'eggs/catalog',
        component: pageRoute('page.admin.eggs.catalog'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'tags',
        staticData: { nav: { label: 'Tags', icon: 'tag' } },
        validateSearch: parseTagListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminTagsQueryOptions(adminTagListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: pageRoute('page.admin.tags'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'extensions',
        staticData: { nav: { label: 'Extensions', icon: 'blocks' } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminExtensionsQueryOptions(), revalidateIfStale: true });
        },
        component: pageRoute('page.admin.extensions'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'themes',
        staticData: { nav: { label: 'Themes', icon: 'palette' } },
        loader: async ({ context }) => {
            await context.queryClient.ensureQueryData({ ...adminThemesQueryOptions(), revalidateIfStale: true });
        },
        component: pageRoute('page.admin.themes'),
    }),
    createRoute({
        getParentRoute: () => panelRoute,
        path: 'api',
        staticData: { nav: { label: 'API Keys', icon: 'key-round' } },
        validateSearch: parseApiKeyListSearch,
        loaderDeps: ({ search }) => ({ page: search.page ?? 1, filter: search.filter ?? '', sort: search.sort }),
        loader: async ({ context, deps }) => {
            await context.queryClient.ensureQueryData({
                ...adminApiKeysQueryOptions(adminApiKeyListQueryParams(deps.page, deps.filter, deps.sort)),
                revalidateIfStale: true,
            });
        },
        component: pageRoute('page.admin.apiKeys'),
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
            // The workbench sits beside the admin layout rather than under it, but still owns its path.
            admin: [...[...panelChildren, ...panelResourceRoutes].map(declaredPath), 'theme-workbench'],
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
            themeWorkbenchRoute,
        ]),
    ]);
}
