import { useEffect, useMemo } from 'react';
import { useQueries } from '@tanstack/react-query';
import { useNavigate } from '@tanstack/react-router';
import { useCurrentUser } from '@/api/account/queries';
import {
    serverResourceUsageQueryOptions,
    serverResourceUsageRefetchInterval,
    useAccountServers,
    useServerResourceUsage,
    type ClientStatsAttributes,
} from '@/api/account/servers/queries';
import type { Server } from '@/api/server/types';
import { httpErrorToHuman } from '@/api/http';
import { relationshipData } from '@/api/relationships';
import { ip, mbToBytes } from '@/lib/formatters';
import { getPageSearch, useDashboardSearch } from '@/router/pageSearch';
import { createTestServer, createTestServerCard } from './fixtures';
import { implementViews } from './registry';

export type ServerPower = 'offline' | 'stopped' | 'starting' | 'running' | 'stopping';
export interface ServerCardMetric {
    readonly value: number;
    readonly limit: number;
    readonly alarm: boolean;
}
export type ServerCardState =
    | { readonly kind: 'loading' }
    | {
          readonly kind: 'unavailable';
          readonly reason:
              | 'suspended'
              | 'connection-error'
              | 'maintenance'
              | 'transferring'
              | 'installing'
              | 'restoring-backup'
              | 'unavailable';
      }
    | {
          readonly kind: 'ready';
          readonly power: ServerPower;
          readonly cpu: ServerCardMetric;
          readonly memory: ServerCardMetric;
          readonly disk: ServerCardMetric;
      };
export interface ServerCardModel {
    readonly identifier: string;
    readonly uuid: string;
    readonly name: string;
    readonly description: string | null;
    readonly address: string | null;
    readonly state: ServerCardState;
}

/** Whose servers the dashboard lists; only root admins can list other users' servers. */
export type DashboardScope = 'mine' | 'others';
export interface DashboardModel {
    /** The server list failed to load, so the page shows this message instead. */
    readonly error: string | null;
    readonly retry: () => void;
}
export interface ServerListItem {
    readonly server: Server;
    /** Null until the server reports its power state, or while it cannot. */
    readonly power: ServerPower | null;
}
export interface ServerListPagination {
    readonly total: number;
    readonly count: number;
    readonly perPage: number;
    readonly currentPage: number;
    readonly totalPages: number;
}
export interface ServerListModel {
    readonly servers: readonly ServerListItem[];
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

const powerStates = (results: { data?: { attributes: ClientStatsAttributes } }[]) =>
    results.map((result) => result.data?.attributes.current_state ?? null);

function useServerListModel(): ServerListModel {
    const navigate = useNavigate();
    const { query, scope, rootAdmin } = useServerListQuery();
    const servers = query.data;
    const power = useQueries({
        queries: (servers?.data ?? []).map((server) =>
            Object.assign(serverResourceUsageQueryOptions(server.attributes.uuid), {
                enabled: tracksResources(server),
                refetchInterval: serverResourceUsageRefetchInterval,
            })
        ),
        combine: powerStates,
    });
    const type = scope === 'others' ? 'admin' : undefined;

    useEffect(() => {
        if (servers && servers.meta.pagination.current_page > 1 && !servers.data.length) {
            void navigate({ to: '/', search: type ? { type } : {}, replace: true, viewTransition: false });
        }
    }, [navigate, type, servers]);

    return useMemo(() => {
        const pagination = servers?.meta.pagination;

        return {
            servers: (servers?.data ?? []).map((server, index) => ({ server, power: power[index] ?? null })),
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
    }, [servers, power, scope, rootAdmin, type, navigate]);
}

const isAlarmState = (current: number, limit: number): boolean => limit > 0 && current / (limit * 1024 * 1024) >= 0.9;

type ServerAlarms = { cpu: boolean; memory: boolean; disk: boolean };

const computeAlarms = (
    stats: ClientStatsAttributes | undefined,
    limits: Server['attributes']['limits']
): ServerAlarms => {
    if (!stats) {
        return { cpu: false, memory: false, disk: false };
    }

    return {
        cpu: limits.cpu === 0 ? false : stats.resources.cpu_absolute >= limits.cpu * 0.9,
        memory: isAlarmState(stats.resources.memory_bytes, limits.memory),
        disk: limits.disk === 0 ? false : isAlarmState(stats.resources.disk_bytes, limits.disk),
    };
};

function resolveState(
    attributes: Server['attributes'],
    stats: ClientStatsAttributes | undefined,
    suspended: boolean | undefined
): ServerCardState {
    if (suspended) {
        return { kind: 'unavailable', reason: attributes.status === 'suspended' ? 'suspended' : 'connection-error' };
    }

    if (attributes.is_node_under_maintenance) {
        return { kind: 'unavailable', reason: 'maintenance' };
    }

    if (stats) {
        const alarms = computeAlarms(stats, attributes.limits);

        return {
            kind: 'ready',
            power: stats.current_state,
            cpu: { value: stats.resources.cpu_absolute, limit: attributes.limits.cpu, alarm: alarms.cpu },
            memory: {
                value: stats.resources.memory_bytes,
                limit: mbToBytes(attributes.limits.memory),
                alarm: alarms.memory,
            },
            disk: { value: stats.resources.disk_bytes, limit: mbToBytes(attributes.limits.disk), alarm: alarms.disk },
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

function useServerCardModel({ server }: { readonly server: Server }): ServerCardModel {
    const { attributes } = server;
    const stats = useServerResourceUsage(attributes.uuid, tracksResources(server)).data?.attributes;
    const suspended = stats?.is_suspended || attributes.status === 'suspended';

    return useMemo(
        () => ({
            identifier: attributes.identifier,
            uuid: attributes.uuid,
            name: attributes.name,
            description: attributes.description,
            address:
                relationshipData(attributes.relationships?.allocations)
                    .filter((allocation) => allocation.attributes.is_default)
                    .map(
                        ({ attributes: allocation }) => `${allocation.ip_alias || ip(allocation.ip)}:${allocation.port}`
                    )
                    .join('') || null,
            state: resolveState(attributes, stats, suspended),
        }),
        [attributes, stats, suspended]
    );
}

const noop = () => {};

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
            servers: () => ({
                servers: [
                    { server: createTestServer({ identifier: 'a1b2c3d4', name: 'Survival' }), power: 'running' },
                    { server: createTestServer({ identifier: 'e5f6a7b8', name: 'Creative' }), power: 'offline' },
                ],
                loading: false,
                pagination: { total: 2, count: 2, perPage: 50, currentPage: 1, totalPages: 1 },
                scope: 'mine',
                canViewOthers: true,
                canCreateServers: true,
                actions: { setPage: noop, setScope: noop },
            }),
            empty: () => ({
                servers: [],
                loading: false,
                pagination: { total: 0, count: 0, perPage: 50, currentPage: 1, totalPages: 1 },
                scope: 'mine',
                canViewOthers: false,
                canCreateServers: false,
                actions: { setPage: noop, setScope: noop },
            }),
            loading: () => ({
                servers: [],
                loading: true,
                pagination: null,
                scope: 'mine',
                canViewOthers: true,
                canCreateServers: true,
                actions: { setPage: noop, setScope: noop },
            }),
        },
    },
    'dashboard.serverCard': {
        use: useServerCardModel,
        fixtureInput: (card) => ({
            server: createTestServer({ identifier: card.identifier, uuid: card.uuid, name: card.name }),
        }),
        fixtures: {
            running: () => createTestServerCard(),
            offline: () =>
                createTestServerCard({
                    state: {
                        kind: 'ready',
                        power: 'offline',
                        cpu: { value: 0, limit: 0, alarm: false },
                        memory: { value: 0, limit: 0, alarm: false },
                        disk: { value: 512 * 1024 ** 2, limit: 0, alarm: false },
                    },
                }),
            suspended: () => createTestServerCard({ state: { kind: 'unavailable', reason: 'suspended' } }),
            loading: () => createTestServerCard({ address: null, state: { kind: 'loading' } }),
        },
    },
});
