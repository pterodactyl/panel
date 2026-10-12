import { useMemo, type ComponentType } from 'react';
import type { Server } from '@/api/server/types';
import { relationshipData } from '@/api/relationships';
import { CHART_WINDOW_MS, type ServerGraphPoint, type ServerNetworkPoint } from '@/components/server/console/chart';
import type { PowerAction } from '@/components/server/console/types';
import { createSampleTerminal, useServerTerminal } from '@/components/server/console/useServerTerminal';
import { SocketRequest } from '@/components/server/events';
import { ip, mbToBytes } from '@/lib/formatters';
import { usePermissions } from '@/plugins/usePermissions';
import {
    useServerGraphs,
    useServerStats,
    useServerStatus,
    useSocketConnected,
    useSocketInstance,
} from '@/state/server';
import { createTestServer } from '../fixtures';
import { viewPage } from '../pages';
import { defineView } from '../registry';
import { useLoadedServer } from './loadedServer';

export type { ServerGraphPoint, ServerNetworkPoint };

/** What Wings reports the server doing; null until it has. */
export type ServerRunState = 'offline' | 'starting' | 'running' | 'stopping';

export interface ServerConsoleModel {
    readonly name: string;
    readonly description: string | null;
    /** Why most actions are unavailable right now, shown above the console. */
    readonly notice: 'maintenance' | 'installing' | 'transferring' | null;
    /** The server as the API returns it, which the console slots receive. */
    readonly server: Server;
}

export interface TerminalCanvasProps {
    readonly className?: string;
}
export interface ServerConsoleCommand {
    /** The user may send commands; without it there is no command line. */
    readonly allowed: boolean;
    /** The websocket is down, so nothing can be sent. */
    readonly disabled: boolean;
    readonly value: string;
    /** Sent commands, newest first. */
    readonly history: readonly string[];
    readonly setValue: (value: string) => void;
    /** Puts the previous command of the history into the value. */
    readonly previous: () => void;
    /** Puts the next, more recent command of the history into the value, or clears it. */
    readonly next: () => void;
    /** Sends the value and adds it to the history; an empty value does nothing. */
    readonly submit: () => void;
}
export interface ServerTerminalModel {
    /** The terminal canvas, drawn with the `--terminal-*` tokens; give it a sized box. */
    readonly Terminal: ComponentType<TerminalCanvasProps>;
    /** The terminal or its connection is still starting. */
    readonly loading: boolean;
    /** The terminal could not be loaded; `retry` loads it again. */
    readonly failed: boolean;
    readonly retry: () => void;
    /** The reader scrolled up from the newest output. */
    readonly scrolledUp: boolean;
    readonly scrollToBottom: () => void;
    readonly command: ServerConsoleCommand;
}

export interface ServerPowerModel {
    readonly state: ServerRunState | null;
    readonly connected: boolean;
    readonly permissions: { readonly start: boolean; readonly stop: boolean; readonly restart: boolean };
    /** Which signals can be sent right now. */
    readonly enabled: {
        readonly start: boolean;
        readonly stop: boolean;
        readonly restart: boolean;
        readonly kill: boolean;
    };
    /** The server is stopping, so stop becomes kill, which can corrupt data; confirm it first. */
    readonly killable: boolean;
    readonly actions: {
        readonly start: () => void;
        readonly stop: () => void;
        readonly restart: () => void;
        readonly kill: () => void;
    };
    /** The server as the API returns it, which the power slots receive. */
    readonly server: Server;
}

/** A usage value against its limit, where a limit of 0 means unlimited. */
export interface ServerStatMetric {
    readonly value: number;
    readonly limit: number;
    /** Above 80% of the limit warns, above 90% is critical. */
    readonly alarm: 'warning' | 'critical' | null;
}
export interface ServerStatsModel {
    readonly state: ServerRunState | null;
    /** The primary allocation as "host:port". */
    readonly address: string | null;
    /** Milliseconds since the server started; 0 while it is not running. */
    readonly uptime: number;
    /** Percent of one core. */
    readonly cpu: ServerStatMetric;
    /** Bytes. */
    readonly memory: ServerStatMetric;
    /** Bytes. */
    readonly disk: ServerStatMetric;
    /** Bytes received and sent since the server started. */
    readonly network: { readonly rx: number; readonly tx: number };
}

/** Rolling samples any chart can draw; `t` is a performance.now() timestamp, newest last. */
export interface ServerGraphsModel {
    /** The server is running, so the graphs keep moving between samples. */
    readonly live: boolean;
    /** The time each graph shows, in milliseconds. */
    readonly windowMs: number;
    /** CPU load in percent; `limit` is 0 when unlimited. */
    readonly cpu: { readonly points: readonly ServerGraphPoint[]; readonly limit: number };
    /** Memory in MiB; `limit` is 0 when unlimited. */
    readonly memory: { readonly points: readonly ServerGraphPoint[]; readonly limit: number };
    /** Bytes received and sent between samples. */
    readonly network: { readonly points: readonly ServerNetworkPoint[] };
}

declare module '../registry' {
    interface ViewModels {
        'page.server.console': ServerConsoleModel;
        'server.terminal': ServerTerminalModel;
        'server.power': ServerPowerModel;
        'server.stats': ServerStatsModel;
        'server.graphs': ServerGraphsModel;
    }
    interface ViewInputs {
        /** The console stays mounted while another server page shows. */
        'page.server.console': { readonly active: boolean };
    }
}

function consoleNotice({ attributes }: Server): ServerConsoleModel['notice'] {
    if (attributes.is_node_under_maintenance) {
        return 'maintenance';
    }

    if (attributes.status === 'installing' || attributes.status === 'install_failed') {
        return 'installing';
    }

    return attributes.is_transferring ? 'transferring' : null;
}

function useServerConsoleModel(): ServerConsoleModel {
    const server = useLoadedServer();

    return useMemo(
        () => ({
            name: server.attributes.name,
            description: server.attributes.description,
            notice: consoleNotice(server),
            server,
        }),
        [server]
    );
}

function useServerPowerModel(): ServerPowerModel {
    const server = useLoadedServer();
    const state = useServerStatus();
    const connected = useSocketConnected();
    const instance = useSocketInstance();
    const [start, stop, restart] = usePermissions(['control.start', 'control.stop', 'control.restart']);

    return useMemo(() => {
        const send = (action: PowerAction) => () => instance?.send(SocketRequest.SET_STATE, action);

        return {
            state,
            connected,
            permissions: { start, stop, restart },
            enabled: {
                start: connected && state === 'offline',
                stop: connected && state !== 'offline',
                restart: connected && state !== null,
                kill: connected,
            },
            killable: state === 'stopping',
            actions: { start: send('start'), stop: send('stop'), restart: send('restart'), kill: send('kill') },
            server,
        };
    }, [server, state, connected, instance, start, stop, restart]);
}

function primaryAddress({ attributes }: Server): string | null {
    const allocation = relationshipData(attributes.relationships?.allocations).find(
        (candidate) => candidate.attributes.is_default
    )?.attributes;

    return allocation ? `${allocation.ip_alias || ip(allocation.ip)}:${allocation.port}` : null;
}

function alarm(value: number, limit: number): ServerStatMetric['alarm'] {
    const used = limit ? value / limit : 0;

    if (used > 0.9) {
        return 'critical';
    }

    return used > 0.8 ? 'warning' : null;
}

const metric = (value: number, limit: number): ServerStatMetric => ({ value, limit, alarm: alarm(value, limit) });

function useServerStatsModel(): ServerStatsModel {
    const server = useLoadedServer();
    const state = useServerStatus();
    const stats = useServerStats();

    return useMemo(() => {
        const { limits } = server.attributes;

        return {
            state,
            address: primaryAddress(server),
            uptime: stats.uptime,
            cpu: metric(stats.cpu, limits.cpu),
            memory: metric(stats.memory, mbToBytes(limits.memory)),
            disk: metric(stats.disk, mbToBytes(limits.disk)),
            network: { rx: stats.rx, tx: stats.tx },
        };
    }, [server, state, stats]);
}

function useServerGraphsModel(): ServerGraphsModel {
    const { cpu, memory } = useLoadedServer().attributes.limits;
    const state = useServerStatus();
    const graphs = useServerGraphs();

    return useMemo(
        () => ({
            live: state === 'starting' || state === 'running' || state === 'stopping',
            windowMs: CHART_WINDOW_MS,
            cpu: { points: graphs.cpu, limit: cpu },
            memory: { points: graphs.memory, limit: memory },
            network: { points: graphs.network },
        }),
        [state, graphs, cpu, memory]
    );
}

const noop = () => {};

const PRELUDE = '\u001b[1m\u001b[33mcontainer@pterodactyl~ \u001b[0m';

const RunningTerminal = createSampleTerminal([
    `${PRELUDE}Server marked as starting...`,
    '[12:00:01 INFO]: Starting minecraft server version 1.21.4',
    '[12:00:02 INFO]: Loading properties',
    '[12:00:04 INFO]: Preparing level "world"',
    '[12:00:09 INFO]: Done (7.412s)! For help, type "help"',
    `${PRELUDE}Server marked as running...`,
]);
const OfflineTerminal = createSampleTerminal([`${PRELUDE}Server marked as offline...`]);

const command = (options: Partial<ServerConsoleCommand> = {}): ServerConsoleCommand => ({
    allowed: true,
    disabled: false,
    value: '',
    history: ['say Hello', 'list'],
    setValue: noop,
    previous: noop,
    next: noop,
    submit: noop,
    ...options,
});

const terminalFixture = (options: Partial<ServerTerminalModel> = {}): ServerTerminalModel => ({
    Terminal: RunningTerminal,
    loading: false,
    failed: false,
    retry: noop,
    scrolledUp: false,
    scrollToBottom: noop,
    command: command(),
    ...options,
});

const fixtureServer = (status: Server['attributes']['status'] = null) =>
    createTestServer({ identifier: 'a1b2c3d4', name: 'Survival', status });

const powerFixture = (state: ServerRunState | null, options: Partial<ServerPowerModel> = {}): ServerPowerModel => ({
    state,
    connected: true,
    permissions: { start: true, stop: true, restart: true },
    enabled: {
        start: state === 'offline',
        stop: state !== 'offline',
        restart: state !== null,
        kill: true,
    },
    killable: state === 'stopping',
    actions: { start: noop, stop: noop, restart: noop, kill: noop },
    server: fixtureServer(),
    ...options,
});

const GiB = 1024 ** 3;

const statsFixture = (state: ServerRunState | null, options: Partial<ServerStatsModel> = {}): ServerStatsModel => ({
    state,
    address: '203.0.113.10:25565',
    uptime: state === 'running' ? 93_784_000 : 0,
    cpu: metric(state === 'running' ? 37.52 : 0, 100),
    memory: metric(state === 'running' ? 1.4 * GiB : 0, 2 * GiB),
    disk: metric(3.2 * GiB, 10 * GiB),
    network: state === 'running' ? { rx: 48_234_112, tx: 9_812_004 } : { rx: 0, tx: 0 },
    ...options,
});

/** Samples a second apart ending now, so a fixture's graphs sit where live ones would. */
function graphsFixture(live: boolean): ServerGraphsModel {
    const now = performance.now();
    const times = Array.from({ length: live ? 20 : 0 }, (_, index) => now - (19 - index) * 1000);

    return {
        live,
        windowMs: CHART_WINDOW_MS,
        cpu: { points: times.map((t, index) => ({ t, value: 30 + 12 * Math.sin(index / 3) })), limit: 100 },
        memory: { points: times.map((t, index) => ({ t, value: 1300 + index * 6 })), limit: 2048 },
        network: {
            points: times.map((t, index) => ({
                t,
                rx: 40_000 + 9_000 * Math.cos(index / 2),
                tx: 12_000 + index * 300,
            })),
        },
    };
}

export const consoleViews = [
    defineView('page.server.console', {
        ...viewPage('page.server.console'),
        use: useServerConsoleModel,
        slots: ['server.console.before', 'server.console.after'],
        fixtureInput: () => ({ active: true }),
        fixtures: {
            default: () => ({
                name: 'Survival',
                description: 'A vanilla world.',
                notice: null,
                server: fixtureServer(),
            }),
            installing: () => ({
                name: 'Survival',
                description: null,
                notice: 'installing',
                server: fixtureServer('installing'),
            }),
            maintenance: () => ({
                name: 'Survival',
                description: null,
                notice: 'maintenance',
                server: fixtureServer(),
            }),
        },
    }),
    defineView('server.terminal', {
        context: 'server',
        use: useServerTerminal,
        fixtures: {
            running: () => terminalFixture(),
            connecting: () =>
                terminalFixture({ Terminal: OfflineTerminal, loading: true, command: command({ disabled: true }) }),
            failed: () => terminalFixture({ Terminal: OfflineTerminal, failed: true }),
            scrolledUp: () => terminalFixture({ scrolledUp: true }),
            readOnly: () => terminalFixture({ command: command({ allowed: false }) }),
        },
    }),
    defineView('server.power', {
        context: 'server',
        use: useServerPowerModel,
        slots: ['server.console.power.before', 'server.console.power.after'],
        fixtures: {
            running: () => powerFixture('running'),
            offline: () => powerFixture('offline'),
            starting: () => powerFixture('starting'),
            stopping: () => powerFixture('stopping'),
            disconnected: () =>
                powerFixture('running', {
                    connected: false,
                    enabled: { start: false, stop: false, restart: false, kill: false },
                }),
            noPermission: () => powerFixture('running', { permissions: { start: false, stop: false, restart: false } }),
        },
    }),
    defineView('server.stats', {
        context: 'server',
        use: useServerStatsModel,
        fixtures: {
            running: () => statsFixture('running'),
            offline: () => statsFixture('offline'),
            starting: () => statsFixture('starting'),
            alarms: () =>
                statsFixture('running', {
                    cpu: metric(96, 100),
                    memory: metric(1.7 * GiB, 2 * GiB),
                    disk: metric(9.5 * GiB, 10 * GiB),
                }),
            unlimited: () =>
                statsFixture('running', {
                    address: null,
                    cpu: metric(137.5, 0),
                    memory: metric(3 * GiB, 0),
                    disk: metric(12 * GiB, 0),
                }),
        },
    }),
    defineView('server.graphs', {
        context: 'server',
        use: useServerGraphsModel,
        fixtures: { running: () => graphsFixture(true), offline: () => graphsFixture(false) },
    }),
];
