import { useEffect, useRef } from 'react';
import { useCurrentServer } from '@/api/server/queries';
import { useServerStatus } from '@/state/server';
import { SocketEvent } from '@/components/server/events';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { CHART_WINDOW_MS, type ChartPoint, useRollingData } from '@/components/server/console/chart';
import AreaChart from '@/components/server/console/AreaChart';
import type { ChartSeries } from '@/components/server/console/types';
import { parseServerStatsPayload } from '@/components/server/console/stats';
import { bytesToString } from '@/lib/formatters';
import { CloudDownload, CloudUpload } from 'lucide-react';
import ChartBlock from '@/components/server/console/ChartBlock';

const CYAN = 'var(--chart-1)';
const YELLOW = 'var(--chart-2)';
const MIB = 1024 * 1024;

const SINGLE_KEYS = ['value'] as const;
const NETWORK_KEYS = ['tx', 'rx'] as const;

const SINGLE_SERIES: ChartSeries[] = [{ dataKey: 'value', color: CYAN }];
const NETWORK_SERIES: ChartSeries[] = [
    { dataKey: 'tx', color: CYAN },
    { dataKey: 'rx', color: YELLOW },
];

const trim = (value: number, decimals: number) => String(Number(value.toFixed(decimals)));

const formatCpu = (value: number) => `${trim(value, value < 10 ? 1 : 0)}%`;
const formatBytes = (bytes: number) => (bytes < 1 ? '0 B' : bytesToString(bytes, 1).replace('Bytes', 'B'));
const formatRate = (bytes: number) => `${formatBytes(bytes)}/s`;

const latest = (data: ChartPoint[], key: string): number | null => {
    const value = data[data.length - 1]?.[key];

    return value === null || value === undefined ? null : value;
};

export default function StatGraphs() {
    const status = useServerStatus();
    const limits = useCurrentServer()!.attributes.limits;
    const previous = useRef<Record<'tx' | 'rx', number>>({ tx: -1, rx: -1 });

    const { data: cpuData, push: pushCpu, clear: clearCpu } = useRollingData(SINGLE_KEYS);
    const { data: memoryData, push: pushMemory, clear: clearMemory } = useRollingData(SINGLE_KEYS);
    const { data: networkData, push: pushNetwork, clear: clearNetwork } = useRollingData(NETWORK_KEYS);

    const live = status === 'starting' || status === 'running' || status === 'stopping';

    useEffect(() => {
        if (status === 'offline') {
            clearCpu();
            clearMemory();
            clearNetwork();
            previous.current = { tx: -1, rx: -1 };
        }
    }, [clearCpu, clearMemory, clearNetwork, status]);

    useWebsocketEvent(SocketEvent.STATS, (data: string) => {
        const values = parseServerStatsPayload(data);
        if (!values) {
            return;
        }

        const t = performance.now();
        pushCpu({ value: values.cpu_absolute }, t);
        pushMemory({ value: values.memory_bytes }, t);
        pushNetwork(
            {
                tx: previous.current.tx < 0 ? 0 : Math.max(0, values.network.tx_bytes - previous.current.tx),
                rx: previous.current.rx < 0 ? 0 : Math.max(0, values.network.rx_bytes - previous.current.rx),
            },
            t
        );

        previous.current = { tx: values.network.tx_bytes, rx: values.network.rx_bytes };
    });

    const cpuLimit = limits.cpu;
    const memoryLimitBytes = limits.memory * MIB;
    const cpu = latest(cpuData, 'value');
    const memory = latest(memoryData, 'value');
    const tx = latest(networkData, 'tx');
    const rx = latest(networkData, 'rx');
    const offline = <span className={'text-muted-foreground'}>{live ? '—' : 'Offline'}</span>;

    return (
        <>
            <ChartBlock
                title={'CPU Load'}
                value={cpu === null ? offline : formatCpu(cpu)}
                caption={cpuLimit ? `of ${cpuLimit}%` : 'no limit'}
                usage={cpuLimit && cpu !== null ? cpu / cpuLimit : null}
            >
                <AreaChart
                    data={cpuData}
                    series={SINGLE_SERIES}
                    live={live}
                    windowMs={CHART_WINDOW_MS}
                    floor={20}
                    limit={cpuLimit}
                    tickFormatter={formatCpu}
                />
            </ChartBlock>
            <ChartBlock
                title={'Memory'}
                value={memory === null ? offline : formatBytes(memory)}
                caption={
                    memoryLimitBytes && memory !== null
                        ? `of ${formatBytes(memoryLimitBytes)} · ${trim((memory / memoryLimitBytes) * 100, 1)}%`
                        : 'no limit'
                }
                usage={memoryLimitBytes && memory !== null ? memory / memoryLimitBytes : null}
            >
                <AreaChart
                    data={memoryData}
                    series={SINGLE_SERIES}
                    live={live}
                    windowMs={CHART_WINDOW_MS}
                    floor={64 * MIB}
                    limit={memoryLimitBytes}
                    binary
                    tickFormatter={formatBytes}
                />
            </ChartBlock>
            <ChartBlock
                title={'Network'}
                value={
                    rx === null || tx === null ? (
                        offline
                    ) : (
                        <span className={'flex items-baseline gap-4'}>
                            <span className={'flex items-baseline gap-1.5'} title={'Inbound'}>
                                <CloudDownload className={'h-4 w-4 self-center text-chart-2'} />
                                {formatRate(rx)}
                            </span>
                            <span className={'flex items-baseline gap-1.5'} title={'Outbound'}>
                                <CloudUpload className={'h-4 w-4 self-center text-chart-1'} />
                                {formatRate(tx)}
                            </span>
                        </span>
                    )
                }
            >
                <AreaChart
                    data={networkData}
                    series={NETWORK_SERIES}
                    live={live}
                    windowMs={CHART_WINDOW_MS}
                    floor={1024}
                    binary
                    tickFormatter={formatRate}
                />
            </ChartBlock>
        </>
    );
}
