import React, { useCallback, useEffect, useState } from 'react';
import Chart, { ChartConfiguration } from 'chart.js';
import { ServerContext } from '@/state/server';
import { bytesToMegabytes } from '@/helpers';
import merge from 'lodash-es/merge';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { faMemory } from '@fortawesome/free-solid-svg-icons/faMemory';
import { faMicrochip } from '@fortawesome/free-solid-svg-icons/faMicrochip';

const chartDefaults: ChartConfiguration = {
    type: 'line',
    options: {
        legend: {
            display: false,
        },
        tooltips: {
            enabled: false,
        },
        animation: {
            duration: 250,
        },
        elements: {
            point: {
                radius: 0,
            },
            line: {
                tension: 0.1,
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
                ticks: {
                    fontSize: 10,
                    fontFamily: '"IBM Plex Mono", monospace',
                    fontColor: 'rgb(229, 232, 235)',
                    min: 0,
                    beginAtZero: true,
                    maxTicksLimit: 5,
                },
            } ],
        },
    },
};

const createDefaultChart = (ctx: CanvasRenderingContext2D, options?: ChartConfiguration): Chart => new Chart(ctx, {
    ...merge({}, chartDefaults, options),
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

export default () => {
    const status = ServerContext.useStoreState(state => state.status.value);
    const limits = ServerContext.useStoreState(state => state.server.data!.limits);
    const { connected, instance } = ServerContext.useStoreState(state => state.socket);

    const [ memory, setMemory ] = useState<Chart>();
    const [ cpu, setCpu ] = useState<Chart>();

    const memoryRef = useCallback<(node: HTMLCanvasElement | null) => void>(node => {
        if (!node) {
            return;
        }

        setMemory(createDefaultChart(node.getContext('2d')!, {
            options: {
                scales: {
                    yAxes: [ {
                        ticks: {
                            callback: (value) => `${value}Mb  `,
                            suggestedMax: limits.memory,
                        },
                    } ],
                },
            },
        }));
    }, []);

    const cpuRef = useCallback<(node: HTMLCanvasElement | null) => void>(node => {
        if (!node) {
            return;
        }

        setCpu(createDefaultChart(node.getContext('2d')!, {
            options: {
                scales: {
                    yAxes: [ {
                        ticks: {
                            callback: (value) => `${value}%  `,
                        },
                    } ],
                },
            },
        }));
    }, []);

    const statsListener = (data: string) => {
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
    };

    useEffect(() => {
        if (!connected || !instance) {
            return;
        }

        instance.addListener('stats', statsListener);

        return () => {
            instance.removeListener('stats', statsListener);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [ instance, connected, memory, cpu ]);

    return (
        <div className={'flex mt-4'}>
            <TitledGreyBox title={'Memory usage'} icon={faMemory} className={'flex-1 mr-2'}>
                {status !== 'offline' ?
                    <canvas id={'memory_chart'} ref={memoryRef} aria-label={'Server Memory Usage Graph'} role={'img'}/>
                    :
                    <p className={'text-xs text-neutral-400 text-center p-3'}>
                        Server is offline.
                    </p>
                }
            </TitledGreyBox>
            <TitledGreyBox title={'CPU usage'} icon={faMicrochip} className={'flex-1 ml-2'}>
                {status !== 'offline' ?
                    <canvas id={'cpu_chart'} ref={cpuRef} aria-label={'Server CPU Usage Graph'} role={'img'}/>
                    :
                    <p className={'text-xs text-neutral-400 text-center p-3'}>
                        Server is offline.
                    </p>
                }
            </TitledGreyBox>
        </div>
    );
};
