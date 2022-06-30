import React, { useEffect, useRef } from 'react';
import { ServerContext } from '@/state/server';
import { SocketEvent } from '@/components/server/events';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { Line } from 'react-chartjs-2';
import { useChart, useChartTickLabel } from '@/components/server/console/chart';
import { hexToRgba } from '@/lib/helpers';
import { bytesToString } from '@/lib/formatters';
import { CloudDownloadIcon, CloudUploadIcon } from '@heroicons/react/solid';
import { theme } from 'twin.macro';
import ChartBlock from '@/components/server/console/ChartBlock';
import Tooltip from '@/components/elements/tooltip/Tooltip';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { faEthernet, faMemory, faMicrochip } from '@fortawesome/free-solid-svg-icons';
import tw from 'twin.macro'

export default () => {
    const status = ServerContext.useStoreState((state) => state.status.value);
    const limits = ServerContext.useStoreState((state) => state.server.data!.limits);
    const previous = useRef<Record<'tx' | 'rx', number>>({ tx: -1, rx: -1 });

    const cpu = useChartTickLabel('CPU', limits.cpu, '%');
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
                borderColor: !index ? '#32D0D9' : theme('colors.yellow.400'),
                backgroundColor: !index ? 'rgba(15, 178, 184, 0.45)' : hexToRgba(theme('colors.yellow.700'), 0.5),
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

        cpu.push(values.cpu_absolute.toFixed(2));
        memory.push(Math.floor(values.memory_bytes / 1024 / 1024));
        network.push([
            previous.current.tx < 0 ? 0 : Math.max(0, values.network.tx_bytes - previous.current.tx),
            previous.current.rx < 0 ? 0 : Math.max(0, values.network.rx_bytes - previous.current.rx),
        ]);

        previous.current = { tx: values.network.tx_bytes, rx: values.network.rx_bytes };
    });

    return (
        <>
            <TitledGreyBox title={'CPU Usage'} icon={faMicrochip} css={tw`relative`}>
			{status !== 'offline' ?
                <Line {...cpu.props} />
				:
				<p css={tw`text-xl text-neutral-200 text-center p-3`}>
                        Server is offline.
                    </p>
			}
            </TitledGreyBox>
            <TitledGreyBox title={'Memory Usage'} icon={faMemory} css={tw`relative`}>
               {status !== 'offline' ?
                <Line {...memory.props} />
				:
				<p css={tw`text-xl text-neutral-200 text-center p-3`}>
                        Server is offline.
                    </p>
			}
            </TitledGreyBox>
            <TitledGreyBox title={'Network Usage'} icon={faEthernet} css={tw`relative`}>
               {status !== 'offline' ?
                <Line {...network.props} />
				:
				<p css={tw`text-xl text-neutral-200 text-center p-3`}>
                        Server is offline.
                    </p>
			}
            </TitledGreyBox>
        </>
    );
};
