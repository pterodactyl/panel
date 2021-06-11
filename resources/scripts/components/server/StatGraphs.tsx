import React, { useCallback, useState } from 'react';
import Chart, { ChartConfiguration } from 'chart.js';
import { ServerContext } from '@/state/server';
import { bytesToMegabytes } from '@/helpers';
import merge from 'deepmerge';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { faMemory, faMicrochip } from '@fortawesome/free-solid-svg-icons';
import tw from 'twin.macro';
import { SocketEvent } from '@/components/server/events';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { WithTranslation, withTranslation } from 'react-i18next';

const chartDefaults = (ticks?: Chart.TickOptions | undefined): ChartConfiguration => ({
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

const StatGraphs = ({ t }: WithTranslation) => {
    const status = ServerContext.useStoreState(state => state.status.value);
    const limits = ServerContext.useStoreState(state => state.server.data!.limits);

    const [ memory, setMemory ] = useState<Chart>();
    const [ cpu, setCpu ] = useState<Chart>();

    const memoryRef = useCallback<(node: HTMLCanvasElement | null) => void>(node => {
        if (!node) {
            return;
        }

        setMemory(
            new Chart(node.getContext('2d')!, chartDefaults({
                callback: (value) => `${value}Mb  `,
                suggestedMax: limits.memory,
            })),
        );
    }, []);

    const cpuRef = useCallback<(node: HTMLCanvasElement | null) => void>(node => {
        if (!node) {
            return;
        }

        setCpu(
            new Chart(node.getContext('2d')!, chartDefaults({
                callback: (value) => `${value}%  `,
            })),
        );
    }, []);

    useWebsocketEvent(SocketEvent.STATS, (data: string) => {
        let stats: any = {};
        try {
            stats = JSON.parse(data);
        } catch (e) {
            return;
        }

        if (memory && memory.data.datasets) {
            const data = memory.data.datasets[0].data!;

            data.push(bytesToMegabytes(stats.memory_bytes));
            data.shift();

            memory.update({ lazy: true });
        }

        if (cpu && cpu.data.datasets) {
            const data = cpu.data.datasets[0].data!;

            data.push(stats.cpu_absolute);
            data.shift();

            cpu.update({ lazy: true });
        }
    });

    return (
        <div css={tw`flex flex-wrap mt-4`}>
            <div css={tw`w-full sm:w-1/2`}>
                <TitledGreyBox title={t('memory_usage')} icon={faMemory} css={tw`mr-0 sm:mr-4`}>
                    {status !== 'offline' ?
                        <canvas
                            id={'memory_chart'}
                            ref={memoryRef}
                            aria-label={t('memory_usage_graph')}
                            role={'img'}
                        />
                        :
                        <p css={tw`text-xs text-neutral-400 text-center p-3`}>
                            {t('server_offline')}
                        </p>
                    }
                </TitledGreyBox>
            </div>
            <div css={tw`w-full sm:w-1/2 mt-4 sm:mt-0`}>
                <TitledGreyBox title={t('cpu_usage')} icon={faMicrochip} css={tw`ml-0 sm:ml-4`}>
                    {status !== 'offline' ?
                        <canvas id={'cpu_chart'} ref={cpuRef} aria-label={t('cpu_usage_graph')} role={'img'}/>
                        :
                        <p css={tw`text-xs text-neutral-400 text-center p-3`}>
                            {t('server_offline')}
                        </p>
                    }
                </TitledGreyBox>
            </div>
        </div>
    );
};

export default withTranslation('server')(StatGraphs);
