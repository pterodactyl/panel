import { theme } from 'twin.macro';
import * as Icon from 'react-feather';
import { Line } from 'react-chartjs-2';
import { hexToRgba } from '@/lib/helpers';
import { ServerContext } from '@/state/server';
import { bytesToString } from '@/lib/formatters';
import React, { useEffect, useRef } from 'react';
import { SocketEvent } from '@/components/server/events';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import ChartBlock from '@/components/server/console/ChartBlock';
import { useChart, useChartTickLabel } from '@/components/server/console/chart';

export default () => {
    const status = ServerContext.useStoreState((state) => state.status.value);
    const limits = ServerContext.useStoreState((state) => state.server.data!.limits);
    const previous = useRef<Record<'tx' | 'rx', number>>({ tx: -1, rx: -1 });

    const cpu = useChartTickLabel('CPU', limits.cpu, '%', 2);
    const memory = useChartTickLabel('Memory', limits.memory, 'MB');
    const network = useChart('Network', {
        sets: 2,
        options: {
            scales: {
                y: {
                    ticks: {
                        callback(value) {
                            return bytesToString(typeof value === 'string' ? parseInt(value, 10) : value);
                        },
                    },
                },
            },
        },
        callback(opts, index) {
            return {
                ...opts,
                label: !index ? 'Network In' : 'Network Out',
                borderColor: !index ? theme('colors.cyan.400') : theme('colors.green.400'),
                backgroundColor: hexToRgba(!index ? theme('colors.cyan.700') : theme('colors.green.700'), 0.5),
            };
        },
    });

    useEffect(() => {
        if (status === 'offline') {
            cpu.clear();
            memory.clear();
            network.clear();
        }
    }, [status]);

    useWebsocketEvent(SocketEvent.STATS, (data: string) => {
        let values: any = {};
        try {
            values = JSON.parse(data);
        } catch (e) {
            return;
        }
        cpu.push(values.cpu_absolute);
        memory.push(Math.floor(values.memory_bytes / 1024 / 1024));
        network.push([
            previous.current.tx < 0 ? 0 : Math.max(0, values.network.tx_bytes - previous.current.tx),
            previous.current.rx < 0 ? 0 : Math.max(0, values.network.rx_bytes - previous.current.rx),
        ]);

        previous.current = { tx: values.network.tx_bytes, rx: values.network.rx_bytes };
    });

    return (
        <>
            <ChartBlock title={'CPU Load'}>
                <Line {...cpu.props} />
            </ChartBlock>
            <ChartBlock title={'Memory'}>
                <Line {...memory.props} />
            </ChartBlock>
            <ChartBlock
                title={'Network'}
                legend={
                    <>
                        <Tooltip arrow content={'Inbound'}>
                            <Icon.UploadCloud className={'mr-2 w-4 h-4 text-green-400'} />
                        </Tooltip>
                        <Tooltip arrow content={'Outbound'}>
                            <Icon.DownloadCloud className={'w-4 h-4 text-cyan-400'} />
                        </Tooltip>
                    </>
                }
            >
                <Line {...network.props} />
            </ChartBlock>
        </>
    );
};
