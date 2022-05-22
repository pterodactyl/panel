import * as Icon from 'react-feather';
import tw, { TwStyle } from 'twin.macro';
import { ServerContext } from '@/state/server';
import React, { useEffect, useState } from 'react';
import CopyOnClick from '@/components/elements/CopyOnClick';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import UptimeDuration from '@/components/server/UptimeDuration';
import { bytesToHuman, formatIp, megabytesToHuman } from '@/helpers';
import { SocketEvent, SocketRequest } from '@/components/server/events';

type Stats = Record<'memory' | 'cpu' | 'disk' | 'uptime' | 'rx' | 'tx', number>;

function statusToColor (status: string | null, installing: boolean): TwStyle {
    if (installing) {
        status = '';
    }

    switch (status) {
        case 'offline':
            return tw`text-red-500`;
        case 'running':
            return tw`text-green-500`;
        default:
            return tw`text-yellow-500`;
    }
}

const ServerDetailsBlock = () => {
    const [ stats, setStats ] = useState<Stats>({ memory: 0, cpu: 0, disk: 0, uptime: 0, tx: 0, rx: 0 });

    const status = ServerContext.useStoreState(state => state.status.value);
    const connected = ServerContext.useStoreState(state => state.socket.connected);
    const instance = ServerContext.useStoreState(state => state.socket.instance);

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
            tx: stats.network.tx_bytes,
            rx: stats.network.rx_bytes,
            uptime: stats.uptime || 0,
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

    const name = ServerContext.useStoreState(state => state.server.data!.name);
    const isInstalling = ServerContext.useStoreState(state => state.server.data!.isInstalling);
    const isTransferring = ServerContext.useStoreState(state => state.server.data!.isTransferring);
    const limits = ServerContext.useStoreState(state => state.server.data!.limits);
    const primaryAllocation = ServerContext.useStoreState(state => state.server.data!.allocations.filter(alloc => alloc.isDefault).map(
        allocation => (allocation.alias || formatIp(allocation.ip)) + ':' + allocation.port,
    )).toString();

    const diskLimit = limits.disk ? megabytesToHuman(limits.disk) : 'Unlimited';
    const memoryLimit = limits.memory ? megabytesToHuman(limits.memory) : 'Unlimited';
    const cpuLimit = limits.cpu ? limits.cpu + '%' : 'Unlimited';

    return (
        <TitledGreyBox css={tw`break-words`} title={name}>
            <p css={tw`text-xs uppercase`}>
                <div css={tw`flex flex-row`}>
                    <Icon.Circle
                        css={[
                            tw`mr-1`,
                            statusToColor(status, isInstalling || isTransferring),
                        ]}
                        size={16}
                    />
                    &nbsp;{!status ? 'Connecting...' : (isInstalling ? 'Installing' : (isTransferring) ? 'Transferring' : status)}
                    {stats.uptime > 0 &&
                        <span css={tw`ml-2 lowercase`}>
                            (<UptimeDuration uptime={stats.uptime / 1000} />)
                        </span>
                    }
                </div>
            </p>
            <CopyOnClick text={primaryAllocation}>
                <p css={tw`text-xs mt-2`}>
                    <div css={tw`flex flex-row`}>
                        <Icon.Wifi css={tw`mr-1`} size={16} />
                        <code css={tw`ml-1`}>{primaryAllocation}</code>
                    </div>
                </p>
            </CopyOnClick>
            <p css={tw`text-xs mt-2`}>
                <div css={tw`flex flex-row`}>
                    <Icon.Cpu css={tw`mr-1`} size={16} /> {stats.cpu.toFixed(0)}%
                    <span css={tw`text-neutral-500`}> / {cpuLimit}</span>
                </div>
            </p>
            <p css={tw`text-xs mt-2`}>
                <div css={tw`flex flex-row`}>
                    <Icon.PieChart css={tw`mr-1`} size={16} /> {bytesToHuman(stats.memory)}
                    <span css={tw`text-neutral-500`}> / {memoryLimit}</span>
                </div>
            </p>
            <p css={tw`text-xs mt-2`}>
                <div css={tw`flex flex-row`}>
                    <Icon.HardDrive css={tw`mr-1`} size={16} />&nbsp;{bytesToHuman(stats.disk)}
                    <span css={tw`text-neutral-500`}> / {diskLimit}</span>
                </div>
            </p>
            <p css={tw`text-xs mt-2`}>
                <div css={tw`flex flex-row`}>
                    <Icon.Wifi css={tw`mr-1`} size={16} />
                    {bytesToHuman(stats.tx)} / {bytesToHuman(stats.rx)}
                </div>
            </p>
        </TitledGreyBox>
    );
};

export default ServerDetailsBlock;
