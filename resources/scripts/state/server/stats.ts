import { appendChartPoint, chartValue, type ServerGraphs } from '@/components/server/console/chart';
import type { ServerStatsPayload } from '@/components/server/console/stats';
import type { ServerSet } from '@/state/server/types';

/** The latest resource usage Wings reported; every value is 0 until the first report. */
export interface ServerStatsSample {
    readonly cpu: number;
    readonly memory: number;
    readonly disk: number;
    readonly rx: number;
    readonly tx: number;
    /** Milliseconds since the server started. */
    readonly uptime: number;
}

export interface ServerStatsStore {
    current: ServerStatsSample;
    /** Rolling samples for the graphs, `t` being a performance.now() timestamp. */
    graphs: ServerGraphs;
    /** Network totals of the previous report, which traffic per sample is measured from; -1 before one. */
    totals: { tx: number; rx: number };
    push: (payload: ServerStatsPayload, t: number) => void;
    clearGraphs: () => void;
}

export const emptyStatsSample: ServerStatsSample = { cpu: 0, memory: 0, disk: 0, rx: 0, tx: 0, uptime: 0 };
export const emptyGraphs: ServerGraphs = { cpu: [], memory: [], network: [] };

const delta = (total: number, previous: number) => (previous < 0 ? 0 : Math.max(0, total - previous));

export const createStats = (set: ServerSet): ServerStatsStore => ({
    current: emptyStatsSample,
    graphs: emptyGraphs,
    totals: { tx: -1, rx: -1 },
    push: (payload, t) =>
        set(({ stats }) => ({
            stats: {
                ...stats,
                current: {
                    cpu: payload.cpu_absolute,
                    memory: payload.memory_bytes,
                    disk: payload.disk_bytes,
                    rx: payload.network.rx_bytes,
                    tx: payload.network.tx_bytes,
                    uptime: payload.uptime || 0,
                },
                graphs: {
                    cpu: appendChartPoint(stats.graphs.cpu, { t, value: chartValue(payload.cpu_absolute) }),
                    memory: appendChartPoint(stats.graphs.memory, {
                        t,
                        value: chartValue(Math.floor(payload.memory_bytes / 1024 / 1024)),
                    }),
                    network: appendChartPoint(stats.graphs.network, {
                        t,
                        tx: chartValue(delta(payload.network.tx_bytes, stats.totals.tx)),
                        rx: chartValue(delta(payload.network.rx_bytes, stats.totals.rx)),
                    }),
                },
                totals: { tx: payload.network.tx_bytes, rx: payload.network.rx_bytes },
            },
        })),
    clearGraphs: () => set(({ stats }) => ({ stats: { ...stats, graphs: emptyGraphs } })),
});
