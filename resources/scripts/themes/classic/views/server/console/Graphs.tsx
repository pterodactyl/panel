import type { ReactNode } from 'react';
import { CloudDownload, CloudUpload } from 'lucide-react';
import { AreaChart, bytesToString, Tooltip, type ChartSeries } from '@/sdk';
import type { ViewProps } from '@/sdk/theme';

const CYAN = 'var(--chart-1)';
const YELLOW = 'var(--chart-2)';

const VALUE_SERIES: ChartSeries[] = [{ dataKey: 'value', color: CYAN }];
const NETWORK_SERIES: ChartSeries[] = [
    { dataKey: 'tx', color: CYAN },
    { dataKey: 'rx', color: YELLOW },
];

function ChartBlock({ title, legend, children }: { title: string; legend?: ReactNode; children: ReactNode }) {
    return (
        <div className='group relative rounded-sm bg-popover pt-2 shadow-lg border-b-4 border-border'>
            <div className='flex items-center justify-between px-4 py-2'>
                <h3 className='font-header font-medium transition-colors duration-100 group-hover:text-foreground'>
                    {title}
                </h3>
                {legend && <p className='text-sm flex items-center'>{legend}</p>}
            </div>
            <div className='z-10 ml-2'>{children}</div>
        </div>
    );
}

export default function Graphs({ model }: ViewProps<'server.graphs'>) {
    const { live, windowMs } = model;

    return (
        <>
            <ChartBlock title='CPU Load'>
                <AreaChart
                    data={model.cpu.points}
                    series={VALUE_SERIES}
                    live={live}
                    windowMs={windowMs}
                    suggestedMax={model.cpu.limit}
                    tickFormatter={(value) => `${value.toFixed(2)}%`}
                />
            </ChartBlock>
            <ChartBlock title='Memory'>
                <AreaChart
                    data={model.memory.points}
                    series={VALUE_SERIES}
                    live={live}
                    windowMs={windowMs}
                    suggestedMax={model.memory.limit}
                    tickFormatter={(value) => `${value}MiB`}
                />
            </ChartBlock>
            <ChartBlock
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
            </ChartBlock>
        </>
    );
}
