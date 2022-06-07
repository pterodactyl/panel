import tw from 'twin.macro';
import styled from 'styled-components/macro';
import { ServerContext } from '@/state/server';
import React, { useEffect, useState } from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { SocketEvent, SocketRequest } from '@/components/server/events';

interface Stats {
    memory: number;
    cpu: number;
    disk: number;
}

const Bar = styled.div`
    ${tw`h-0.5 bg-cyan-400`};
    transition: 1000ms ease-in-out;
`;

const StatBars = () => {
    const [ stats, setStats ] = useState<Stats>({ memory: 0, cpu: 0, disk: 0 });

    const instance = ServerContext.useStoreState(state => state.socket.instance);
    const connected = ServerContext.useStoreState(state => state.socket.connected);
    const limits = ServerContext.useStoreState(state => state.server.data!.limits);

    const cpuUsed = stats.cpu / (limits.cpu / 100);
    const diskUsed = (stats.disk / 1024 / 1024) / limits.disk * 100;
    const ramUsed = (stats.memory / 1024 / 1024) / limits.memory * 100;

    const statsListener = (data: string) => {
        let stats: any = {};
        try {
            stats = JSON.parse(data);
        } catch (e) {
            return;
        }

        setStats({
            memory: stats.memory_bytes,
            cpu: stats.cpu_absolute,
            disk: stats.disk_bytes,
        });
    };

    useEffect(() => {
        if (!connected || !instance) {
            return;
        }

        instance.addListener(SocketEvent.STATS, statsListener);
        instance.send(SocketRequest.SEND_STATS);

        return () => {
            instance.removeListener(SocketEvent.STATS, statsListener);
        };
    }, [ instance, connected ]);

    return (
        <TitledGreyBox title={'Server Statistics'} css={tw`text-xs uppercase mt-4`}>
            {limits.cpu === 0 ?
                <>
                    <p css={tw`mb-1`}>CPU used ({stats.cpu.toFixed(0)}% of Unlimited)</p>
                    <Bar style={{ width: '100%' }} css={tw`mb-2`} />
                </>
                :
                <>
                    <p css={tw`mb-1`}>CPU used ({stats.cpu.toFixed(0)}%)</p>
                    {cpuUsed > 100 ?
                        <Bar style={{ width: '100%' }} css={tw`mb-2 bg-red-400`} />
                        :
                        <Bar style={{ width: cpuUsed === undefined ? '100%' : `${cpuUsed}%` }} css={tw`mb-2`} />
                    }
                </>
            }
            {limits.memory === 0 ?
                <p css={tw`mb-1`}>RAM used ({ramUsed.toFixed(0)}% of Unlimited)</p>
                :
                <>
                    <p css={tw`mb-1`}>RAM used ({ramUsed.toFixed(0)}%)</p>
                    {ramUsed > 100 ?
                        <Bar style={{ width: '100%' }} css={tw`mb-2 bg-red-400`} />
                        :
                        <Bar style={{ width: ramUsed === undefined ? '100%' : `${ramUsed}%` }} css={tw`mb-2`} />
                    }
                </>
            }
            {limits.memory === 0 ?
                <p css={tw`mb-1`}>Disk used ({diskUsed.toFixed(0)}% of Unlimited)</p>
                :
                <>
                    <p css={tw`mb-1`}>Disk used ({diskUsed.toFixed(0)}%)</p>
                    {diskUsed > 100 ?
                        <Bar style={{ width: '100%' }} css={tw`mb-2 bg-red-400`} />
                        :
                        <Bar style={{ width: diskUsed === undefined ? '100%' : `${diskUsed}%` }} css={tw`mb-2`} />
                    }
                </>
            }
        </TitledGreyBox>
    );
};

export default StatBars;
