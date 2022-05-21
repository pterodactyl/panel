import tw from 'twin.macro';
import merge from 'deepmerge';
import { ServerContext } from '@/state/server';
import Chart, { ChartConfiguration } from 'chart.js';
import { SocketEvent } from '@/components/server/events';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import React, { useCallback, useRef, useState } from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';

const chartDefaults = (ticks?: Chart.TickOptions): ChartConfiguration => ({
    type: 'line',
    options: {
        legend: {
            display: false,
        },
        tooltips: {
            enabled: false,
        },
        animation: {
            duration: 0,
        },
        elements: {
            point: {
                radius: 0,
            },
            line: {
                tension: 0.3,
                backgroundColor: 'rgba(15, 178, 184, 0.45)',
                borderColor: '#32D0D9',
            },
        },
        scales: {
            xAxes: [ {
                ticks: {
                    display: false,
                },
                gridLines: {
                    display: false,
                },
            } ],
            yAxes: [ {
                gridLines: {
                    drawTicks: false,
                    color: 'rgba(229, 232, 235, 0.15)',
                    zeroLineColor: 'rgba(15, 178, 184, 0.45)',
                    zeroLineWidth: 3,
                },
                ticks: merge(ticks || {}, {
                    fontSize: 10,
                    fontFamily: '"IBM Plex Mono", monospace',
                    fontColor: 'rgb(229, 232, 235)',
                    min: 0,
                    beginAtZero: true,
                    maxTicksLimit: 5,
                }),
            } ],
        },
    },
    data: {
        labels: Array(20).fill(''),
        datasets: [
            {
                fill: true,
                data: Array(20).fill(0),
            },
        ],
    },
});

type ChartState = [ (node: HTMLCanvasElement | null) => void, Chart | undefined ];

/**
 * Creates an element ref and a chart instance.
 */
const useChart = (options?: Chart.TickOptions): ChartState => {
    const [ chart, setChart ] = useState<Chart>();

    const ref = useCallback<(node: HTMLCanvasElement | null) => void>(node => {
        if (!node) return;

        const chart = new Chart(node.getContext('2d')!, chartDefaults(options));

        setChart(chart);
    }, []);

    return [ ref, chart ];
};

const updateChartDataset = (chart: Chart | null | undefined, value: Chart.ChartPoint & number): void => {
    if (!chart || !chart.data?.datasets) return;

    const data = chart.data.datasets[0].data!;
    data.push(value);
    data.shift();
    chart.update({ lazy: true });
};

export default () => {
    const status = ServerContext.useStoreState(state => state.status.value);
    const limits = ServerContext.useStoreState(state => state.server.data!.limits);

    const previous = useRef<Record<'tx' | 'rx', number>>({ tx: -1, rx: -1 });
    const [ cpuRef, cpu ] = useChart({ callback: (value) => `${value}%  `, suggestedMax: limits.cpu });
    const [ memoryRef, memory ] = useChart({ callback: (value) => `${value}Mb  `, suggestedMax: limits.memory });
    const [ txRef, tx ] = useChart({ callback: (value) => `${value}Kb/s  ` });
    const [ rxRef, rx ] = useChart({ callback: (value) => `${value}Kb/s  ` });

    useWebsocketEvent(SocketEvent.STATS, (data: string) => {
        let stats: any = {};
        try {
            stats = JSON.parse(data);
        } catch (e) {
            return;
        }

        updateChartDataset(cpu, stats.cpu_absolute);
        updateChartDataset(memory, Math.floor(stats.memory_bytes / 1024 / 1024));
        updateChartDataset(tx, previous.current.tx < 0 ? 0 : Math.max(0, stats.network.tx_bytes - previous.current.tx) / 1024);
        updateChartDataset(rx, previous.current.rx < 0 ? 0 : Math.max(0, stats.network.rx_bytes - previous.current.rx) / 1024);

        previous.current = { tx: stats.network.tx_bytes, rx: stats.network.rx_bytes };
    });

    return (
        <div css={tw`mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4`}>
            <TitledGreyBox title={'Memory usage'}>
                {status !== 'offline' ?
                    <canvas
                        id={'memory_chart'}
                        ref={memoryRef}
                        aria-label={'Server Memory Usage Graph'}
                        role={'img'}
                    />
                    :
                    <p css={tw`text-xs text-neutral-400 text-center p-3`}>
                        Server is offline.
                    </p>
                }
            </TitledGreyBox>
            <TitledGreyBox title={'CPU usage'}>
                {status !== 'offline' ?
                    <canvas id={'cpu_chart'} ref={cpuRef} aria-label={'Server CPU Usage Graph'} role={'img'}/>
                    :
                    <p css={tw`text-xs text-neutral-400 text-center p-3`}>
                        Server is offline.
                    </p>
                }
            </TitledGreyBox>
            <TitledGreyBox title={'Inbound Data'}>
                {status !== 'offline' ?
                    <canvas id={'rx_chart'} ref={rxRef} aria-label={'Server Inbound Data'} role={'img'}/>
                    :
                    <p css={tw`text-xs text-neutral-400 text-center p-3`}>
                        Server is offline.
                    </p>
                }
            </TitledGreyBox>
            <TitledGreyBox title={'Outbound Data'}>
                {status !== 'offline' ?
                    <canvas id={'tx_chart'} ref={txRef} aria-label={'Server Outbound Data'} role={'img'}/>
                    :
                    <p css={tw`text-xs text-neutral-400 text-center p-3`}>
                        Server is offline.
                    </p>
                }
            </TitledGreyBox>
        </div>
    );
};
