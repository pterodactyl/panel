import { CloudDownload, CloudUpload } from 'lucide-react';
import { AreaChart, bytesToString, ChartCard, cn, Tooltip, type ChartSeries } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

const CYAN = 'var(--chart-1)';
const YELLOW = 'var(--chart-2)';

const VALUE_SERIES: ChartSeries[] = [{ dataKey: 'value', color: CYAN }];
const NETWORK_SERIES: ChartSeries[] = [
    { dataKey: 'tx', color: CYAN },
    { dataKey: 'rx', color: YELLOW },
];

export default function Graphs({ model, className }: ViewProps<'server.graphs'>) {
    const { live, windowMs } = model;

    return (
        <div className={cn('grid grid-cols-1 md:grid-cols-3 gap-2 sm:gap-4', className)}>
            <ChartCard title='CPU Load'>
                <AreaChart
                    data={model.cpu.points}
                    series={VALUE_SERIES}
                    live={live}
                    windowMs={windowMs}
                    suggestedMax={model.cpu.limit}
                    tickFormatter={(value) => `${value.toFixed(2)}%`}
                />
            </ChartCard>
            <ChartCard title='Memory'>
                <AreaChart
                    data={model.memory.points}
                    series={VALUE_SERIES}
                    live={live}
                    windowMs={windowMs}
                    suggestedMax={model.memory.limit}
                    tickFormatter={(value) => `${value}MiB`}
                />
            </ChartCard>
            <ChartCard
                title='Network'
                legend={
                    <>
                        <Tooltip arrow content='Inbound'>
                            <CloudDownload className='mr-2 w-4 h-4 text-chart-2' />
                        </Tooltip>
                        <Tooltip arrow content='Outbound'>
                            <CloudUpload className='w-4 h-4 text-chart-1' />
                        </Tooltip>
                    </>
                }
            >
                <AreaChart
                    data={model.network.points}
                    series={NETWORK_SERIES}
                    live={live}
                    windowMs={windowMs}
                    tickFormatter={bytesToString}
                />
            </ChartCard>
        </div>
    );
}
