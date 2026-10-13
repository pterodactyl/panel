import { useEffect, useMemo } from 'react';
import { useQueries, useQueryClient } from '@tanstack/react-query';
import { useNavigate } from '@tanstack/react-router';
import { useCurrentUser } from '@/api/account/queries';
import {
    serverResourceUsageQueryOptions,
    serverResourceUsageRefetchInterval,
    useAccountServers,
    useServerResourceUsage,
    type ClientStatsAttributes,
} from '@/api/account/servers/queries';
import { sendPowerActionInput, useSendPowerAction, type ServerPowerSignal } from '@/api/server/power/queries';
import { selectServerPermissions } from '@/api/server/queries';
import type { Server } from '@/api/server/types';
import { httpErrorToHuman } from '@/api/http';
import { relationshipData } from '@/api/relationships';
import { ip, mbToBytes } from '@/lib/formatters';
import { hasPermission } from '@/plugins/usePermissions';
import { getPageSearch, useDashboardSearch } from '@/router/pageSearch';
import { createTestServer, createTestServerCard } from './fixtures';
import { implementViews } from './registry';
import {
    runState,
    serverConflict,
    statMetric,
    type ServerConflict,
    type ServerRunState,
    type ServerStatMetric,
} from './server/state';

/** Why a server's card shows a notice instead of its usage: a conflict, or Wings cannot be reached. */
export type ServerUnavailableReason = ServerConflict | 'connection-error' | 'unavailable';
export type ServerCardState =
    | { readonly kind: 'loading' }
    | { readonly kind: 'unavailable'; readonly reason: ServerUnavailableReason }
    | {
          readonly kind: 'ready';
          readonly power: ServerRunState;
          /** Percent of one core. */
          readonly cpu: ServerStatMetric;
          /** Bytes. */
          readonly memory: ServerStatMetric;
          /** Bytes. */
          readonly disk: ServerStatMetric;
      };
/** What the user may do with the server, by their subuser permissions. */
export interface ServerCardPermissions {
    readonly start: boolean;
    readonly stop: boolean;
    readonly restart: boolean;
    readonly console: boolean;
}
export interface ServerCardModel {
    readonly identifier: string;
    readonly uuid: string;
    readonly name: string;
    readonly description: string | null;
    readonly address: string | null;
    readonly state: ServerCardState;
    readonly permissions: ServerCardPermissions;
    /** Send a power signal through the client API, with no console connection; the panel reports a failure. */
    readonly actions: { readonly start: () => void; readonly stop: () => void; readonly restart: () => void };
    /** A power signal is on its way. */
    readonly sending: boolean;
}

/** Whose servers the dashboard lists; only root admins can list other users' servers. */
export type DashboardScope = 'mine' | 'others';
export interface DashboardModel {
    /** The server list failed to load, so the page shows this message instead. */
    readonly error: string | null;
    readonly retry: () => void;
}
/** A server of the list: its card's model, with the server as the API returns it for the row slots and the card. */
export interface ServerListItem extends ServerCardModel {
    readonly server: Server;
}
export interface ServerListPagination {
    readonly total: number;
    readonly count: number;
    readonly perPage: number;
    readonly currentPage: number;
    readonly totalPages: number;
}
export interface ServerListTotals {
    /** Servers on every page, from the pagination; 0 until the first page loads. */
    readonly servers: number;
    /** Running servers on the loaded page only. */
    readonly runningOnPage: number;
    /** Offline servers on the loaded page only; unavailable and loading servers are neither. */
    readonly offlineOnPage: number;
}
export interface ServerListModel {
    readonly servers: readonly ServerListItem[];
    readonly totals: ServerListTotals;
    /** The page has not loaded yet. */
    readonly loading: boolean;
    readonly pagination: ServerListPagination | null;
    readonly scope: DashboardScope;
    readonly canViewOthers: boolean;
    readonly canCreateServers: boolean;
    readonly actions: {
        readonly setPage: (page: number) => void;
        readonly setScope: (scope: DashboardScope) => void;
    };
}

declare module './registry' {
    interface ViewModels {
        'page.dashboard': DashboardModel;
        'dashboard.serverList': ServerListModel;
        'dashboard.serverCard': ServerCardModel;
    }
    interface ViewInputs {
        'dashboard.serverCard': { readonly server: Server };
    }
    interface ViewPartNames {
        'dashboard.serverCard': 'identity' | 'address' | 'metrics';
    }
}

function useServerListQuery() {
    const { page, type } = useDashboardSearch();
    const { rootAdmin } = useCurrentUser();
    const scope: DashboardScope = rootAdmin && type === 'admin' ? 'others' : 'mine';

    return { query: useAccountServers({ page, type: scope === 'others' ? 'admin' : undefined }), scope, rootAdmin };
}

function useDashboardModel(): DashboardModel {
    const { error, refetch } = useServerListQuery().query;

    return useMemo(
        () => ({
            error: error ? httpErrorToHuman(error) : null,
            retry: () => void refetch(),
        }),
        [error, refetch]
    );
}

const tracksResources = ({ attributes }: Server): boolean =>
    attributes.status !== 'suspended' && !attributes.is_node_under_maintenance && attributes.uuid.length > 0;

function resolveState(server: Server, stats: ClientStatsAttributes | undefined): ServerCardState {
    const { attributes } = server;

    if (stats?.is_suspended || attributes.status === 'suspended') {
        return { kind: 'unavailable', reason: attributes.status === 'suspended' ? 'suspended' : 'connection-error' };
    }

    if (attributes.is_node_under_maintenance) {
        return { kind: 'unavailable', reason: 'maintenance' };
    }

    if (stats) {
        const { limits } = attributes;

        return {
            kind: 'ready',
            power: runState(stats.current_state),
            cpu: statMetric(stats.resources.cpu_absolute, limits.cpu),
            memory: statMetric(stats.resources.memory_bytes, mbToBytes(limits.memory)),
            disk: statMetric(stats.resources.disk_bytes, mbToBytes(limits.disk)),
        };
    }

    if (attributes.is_transferring) {
        return { kind: 'unavailable', reason: 'transferring' };
    }

    if (attributes.status === 'installing') {
        return { kind: 'unavailable', reason: 'installing' };
    }

    if (attributes.status === 'restoring_backup') {
        return { kind: 'unavailable', reason: 'restoring-backup' };
    }

    return attributes.status ? { kind: 'unavailable', reason: 'unavailable' } : { kind: 'loading' };
}

function cardPermissions(server: Server, rootAdmin = false): ServerCardPermissions {
    // List responses carry no per-server meta; owners and root admins may use every power action.
    const granted =
        server.meta || !(server.attributes.server_owner || rootAdmin) ? selectServerPermissions(server) : ['*'];

    return {
        start: hasPermission(granted, 'control.start'),
        stop: hasPermission(granted, 'control.stop'),
        restart: hasPermission(granted, 'control.restart'),
        console: hasPermission(granted, 'control.console'),
    };
}

const primaryAddress = ({ attributes }: Server): string | null =>
    relationshipData(attributes.relationships?.allocations)
        .filter((allocation) => allocation.attributes.is_default)
        .map(({ attributes: allocation }) => `${allocation.ip_alias || ip(allocation.ip)}:${allocation.port}`)
        .join('') || null;

interface PowerSender {
    send(uuid: string, signal: ServerPowerSignal): void;
    /** The server a signal is on its way to. */
    readonly sending: string | null;
}

/** Sends power signals through the client API and refreshes the server's usage once Wings accepts one. */
function usePowerSender(): PowerSender {
    const queryClient = useQueryClient();
    const { mutate, isPending, variables } = useSendPowerAction();

    return useMemo(
        () => ({
            send: (uuid, signal) =>
                mutate(sendPowerActionInput(uuid, signal), {
                    onSuccess: () =>
                        void queryClient.invalidateQueries({
                            queryKey: serverResourceUsageQueryOptions(uuid).queryKey,
                        }),
                }),
            sending: isPending ? (variables?.path.server_uuid ?? null) : null,
        }),
        [mutate, isPending, variables, queryClient]
    );
}

function cardModel(
    server: Server,
    stats: ClientStatsAttributes | undefined,
    power: PowerSender,
    rootAdmin: boolean
): ServerCardModel {
    const { identifier, uuid, name, description } = server.attributes;
    const send = (signal: ServerPowerSignal) => () => power.send(uuid, signal);

    return {
        identifier,
        uuid,
        name,
        description,
        address: primaryAddress(server),
        state: resolveState(server, stats),
        permissions: cardPermissions(server, rootAdmin),
        actions: { start: send('start'), stop: send('stop'), restart: send('restart') },
        sending: power.sending === uuid,
    };
}

const usageOf = (results: { data?: { attributes: ClientStatsAttributes } }[]) =>
    results.map((result) => result.data?.attributes);

const countPower = (items: readonly ServerListItem[], power: ServerRunState): number =>
    items.filter(({ state }) => state.kind === 'ready' && state.power === power).length;

function useServerListModel(): ServerListModel {
    const navigate = useNavigate();
    const { query, scope, rootAdmin } = useServerListQuery();
    const servers = query.data;
    const power = usePowerSender();
    // The same cache entries as the cards' own queries, so a card and its row never poll twice.
    const usage = useQueries({
        queries: (servers?.data ?? []).map((server) =>
            Object.assign(serverResourceUsageQueryOptions(server.attributes.uuid), {
                enabled: tracksResources(server),
                refetchInterval: serverResourceUsageRefetchInterval,
            })
        ),
        combine: usageOf,
    });
    const type = scope === 'others' ? 'admin' : undefined;

    useEffect(() => {
        if (servers && servers.meta.pagination.current_page > 1 && !servers.data.length) {
            void navigate({ to: '/', search: type ? { type } : {}, replace: true, viewTransition: false });
        }
    }, [navigate, type, servers]);

    return useMemo(() => {
        const pagination = servers?.meta.pagination;
        const items = (servers?.data ?? []).map((server, index) =>
            Object.assign(cardModel(server, usage[index], power, rootAdmin), { server })
        );

        return {
            servers: items,
            totals: {
                servers: pagination?.total ?? 0,
                runningOnPage: countPower(items, 'running'),
                offlineOnPage: countPower(items, 'offline'),
            },
            loading: !servers,
            pagination: pagination
                ? {
                      total: pagination.total,
                      count: pagination.count,
                      perPage: pagination.per_page,
                      currentPage: pagination.current_page,
                      totalPages: pagination.total_pages,
                  }
                : null,
            scope,
            canViewOthers: rootAdmin,
            canCreateServers: rootAdmin,
            actions: {
                setPage: (page: number) =>
                    void navigate({
                        to: '/',
                        search: type ? { ...getPageSearch(page), type } : getPageSearch(page),
                        replace: true,
                        viewTransition: false,
                    }),
                setScope: (next: DashboardScope) =>
                    void navigate({
                        to: '/',
                        search: next === 'others' ? { type: 'admin' } : {},
                        replace: true,
                        viewTransition: false,
                    }),
            },
        };
    }, [servers, usage, power, scope, rootAdmin, type, navigate]);
}

function useServerCardModel({ server }: { readonly server: Server }): ServerCardModel {
    const stats = useServerResourceUsage(server.attributes.uuid, tracksResources(server)).data?.attributes;
    const power = usePowerSender();
    const rootAdmin = useCurrentUser().rootAdmin;

    return useMemo(() => cardModel(server, stats, power, rootAdmin), [server, stats, power, rootAdmin]);
}

/** The cards of the list fixtures' rows, so a card placed in a row shows that row's state. */
const listedCards = new Map<string, ServerCardModel>();

/** A card for a fixture server: its row's, else its conflict when it has one, else running. */
function cardFromServer(server: Server): ServerCardModel {
    const listed = listedCards.get(server.attributes.uuid);

    if (listed) {
        return listed;
    }

    const conflict = serverConflict(server);
    const card = createTestServerCard({
        identifier: server.attributes.identifier,
        uuid: server.attributes.uuid,
        name: server.attributes.name,
        description: server.attributes.description,
        permissions: cardPermissions(server),
    });

    return conflict ? { ...card, state: { kind: 'unavailable', reason: conflict } } : card;
}

const noop = () => {};

const offline: ServerCardState = {
    kind: 'ready',
    power: 'offline',
    cpu: statMetric(0, 100),
    memory: statMetric(0, 1024 ** 3),
    disk: statMetric(800 * 1024 ** 2, 2048 * 1024 ** 2),
};

const listFixture = (
    servers: readonly (readonly [Server, ServerCardState?])[],
    options: Partial<ServerListModel> = {}
): ServerListModel => {
    const items = servers.map(([server, state]) => {
        const derived = cardFromServer(server);
        const card = state ? { ...derived, state } : derived;

        listedCards.set(server.attributes.uuid, card);

        return { ...card, server };
    });

    return {
        servers: items,
        totals: {
            servers: items.length,
            runningOnPage: countPower(items, 'running'),
            offlineOnPage: countPower(items, 'offline'),
        },
        loading: false,
        pagination: { total: items.length, count: items.length, perPage: 50, currentPage: 1, totalPages: 1 },
        scope: 'mine',
        canViewOthers: true,
        canCreateServers: true,
        actions: { setPage: noop, setScope: noop },
        ...options,
    };
};

implementViews({
    'page.dashboard': {
        use: useDashboardModel,
        fixtures: {
            default: () => ({ error: null, retry: noop }),
            error: () => ({ error: 'Request failed with status code 500.', retry: noop }),
        },
    },
    'dashboard.serverList': {
        use: useServerListModel,
        fixtures: {
            servers: () =>
                listFixture([
                    [
                        createTestServer({
                            identifier: 'a1b2c3d4',
                            uuid: 'a1b2c3d4-0000-4000-8000-000000000001',
                            name: 'Survival',
                        }),
                    ],
                    [
                        createTestServer({
                            identifier: 'e5f6a7b8',
                            uuid: 'e5f6a7b8-0000-4000-8000-000000000002',
                            name: 'Creative',
                        }),
                        offline,
                    ],
                    [
                        createTestServer({
                            identifier: 'c9d0e1f2',
                            uuid: 'c9d0e1f2-0000-4000-8000-000000000003',
                            name: 'Lobby',
                            status: 'suspended',
                        }),
                    ],
                ]),
            empty: () => listFixture([], { canViewOthers: false, canCreateServers: false }),
            loading: () => listFixture([], { loading: true, pagination: null }),
        },
    },
    'dashboard.serverCard': {
        use: useServerCardModel,
        fixtureInput: (card) => ({
            server: createTestServer({ identifier: card.identifier, uuid: card.uuid, name: card.name }),
        }),
        fixtureFromInput: ({ server }) => cardFromServer(server),
        fixtures: {
            running: () => createTestServerCard(),
            offline: () =>
                createTestServerCard({
                    state: {
                        kind: 'ready',
                        power: 'offline',
                        cpu: statMetric(0, 0),
                        memory: statMetric(0, 0),
                        disk: statMetric(512 * 1024 ** 2, 0),
                    },
                }),
            suspended: () => createTestServerCard({ state: { kind: 'unavailable', reason: 'suspended' } }),
            loading: () => createTestServerCard({ address: null, state: { kind: 'loading' } }),
        },
    },
});
