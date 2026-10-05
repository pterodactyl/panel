import { useEffect, useRef } from 'react';
import { useCurrentServer } from '@/api/server/queries';
import { useServerStatus } from '@/state/server';
import { SocketEvent } from '@/components/server/events';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { CHART_WINDOW_MS, useRollingData } from '@/components/server/console/chart';
import AreaChart from '@/components/server/console/AreaChart';
import type { ChartSeries } from '@/components/server/console/types';
import { parseServerStatsPayload } from '@/components/server/console/stats';
import { bytesToString } from '@/lib/formatters';
import { CloudDownload, CloudUpload } from 'lucide-react';
import ChartBlock from '@/components/server/console/ChartBlock';
import Tooltip from '@/components/elements/tooltip/Tooltip';

const CYAN = 'var(--chart-1)';
const YELLOW = 'var(--chart-2)';

const SINGLE_KEYS = ['value'] as const;
const NETWORK_KEYS = ['tx', 'rx'] as const;

const CPU_SERIES: ChartSeries[] = [{ dataKey: 'value', color: CYAN }];
const MEMORY_SERIES: ChartSeries[] = [{ dataKey: 'value', color: CYAN }];
const NETWORK_SERIES: ChartSeries[] = [
    { dataKey: 'tx', color: CYAN },
    { dataKey: 'rx', color: YELLOW },
];

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
        }
    }, [clearCpu, clearMemory, clearNetwork, status]);

    useWebsocketEvent(SocketEvent.STATS, (data: string) => {
        const values = parseServerStatsPayload(data);
        if (!values) {
            return;
        }

        const t = performance.now();
        pushCpu({ value: values.cpu_absolute }, t);
        pushMemory({ value: Math.floor(values.memory_bytes / 1024 / 1024) }, t);
        pushNetwork(
            {
                tx: previous.current.tx < 0 ? 0 : Math.max(0, values.network.tx_bytes - previous.current.tx),
                rx: previous.current.rx < 0 ? 0 : Math.max(0, values.network.rx_bytes - previous.current.rx),
            },
            t
        );

        previous.current = { tx: values.network.tx_bytes, rx: values.network.rx_bytes };
    });

    return (
        <>
            <ChartBlock title={'CPU Load'}>
                <AreaChart
                    data={cpuData}
                    series={CPU_SERIES}
                    live={live}
                    windowMs={CHART_WINDOW_MS}
                    suggestedMax={limits.cpu}
                    tickFormatter={(value) => `${value.toFixed(2)}%`}
                />
            </ChartBlock>
            <ChartBlock title={'Memory'}>
                <AreaChart
                    data={memoryData}
                    series={MEMORY_SERIES}
                    live={live}
                    windowMs={CHART_WINDOW_MS}
                    suggestedMax={limits.memory}
                    tickFormatter={(value) => `${value}MiB`}
                />
            </ChartBlock>
            <ChartBlock
                title={'Network'}
                legend={
                    <>
                        <Tooltip arrow content={'Inbound'}>
                            <CloudDownload className={'mr-2 w-4 h-4 text-chart-2'} />
                        </Tooltip>
                        <Tooltip arrow content={'Outbound'}>
                            <CloudUpload className={'w-4 h-4 text-chart-1'} />
                        </Tooltip>
                    </>
                }
            >
                <AreaChart
                    data={networkData}
                    series={NETWORK_SERIES}
                    live={live}
                    windowMs={CHART_WINDOW_MS}
                    tickFormatter={bytesToString}
                />
            </ChartBlock>
        </>
    );
}
