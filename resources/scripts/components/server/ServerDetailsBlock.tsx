import React, { useEffect, useState } from 'react';
import tw, { TwStyle } from 'twin.macro';
import { faCircle, faEthernet, faHdd, faMemory, faMicrochip, faServer, faNetworkWired } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { bytesToHuman, megabytesToHuman, formatIp } from '@/helpers';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { ServerContext } from '@/state/server';
import CopyOnClick from '@/components/elements/CopyOnClick';
import { SocketEvent, SocketRequest } from '@/components/server/events';
import UptimeDuration from '@/components/server/UptimeDuration';

interface Stats {
    memory: number;
    cpu: number;
    disk: number;
    uptime: number;
    networkDataRate: number;
}

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

// Store old values to compare to new values for network speed.
let oldBytesReceived = 0;
let oldBytesSent = 0;

function setOldNetworkValues (bytesReceived: number, bytesSent: number): void {
    oldBytesReceived = bytesReceived;
    oldBytesSent = bytesSent;
}

// Convert bytes to bits per second (bps) by comparing to old values.
function bytesToBps (bytesReceived: number, bytesSent: number): number {
    const bpsReceived = (bytesReceived - oldBytesReceived) * 8;
    const bpsSent = (bytesSent - oldBytesSent) * 8;

    setOldNetworkValues(bytesReceived, bytesSent);

    return bpsReceived + bpsSent;
}

// Convert bits per second (bps) to human readable format.
function bpsToHuman (bps: number): string {
    if (bps < 1000) {
        return `${bps} bps`;
    }

    if (bps < 1000000) {
        return `${(bps / 1000).toFixed(2)} kbps`;
    }

    if (bps < 1000000000) {
        return `${(bps / 1000000).toFixed(2)} mbps`;
    }

    return `${(bps / 1000000000).toFixed(2)} gbps`;
}

const ServerDetailsBlock = () => {
    const [ stats, setStats ] = useState<Stats>({ memory: 0, cpu: 0, disk: 0, uptime: 0, networkDataRate: 0 });

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
            uptime: stats.uptime || 0,
            networkDataRate: bytesToBps(stats.network.rx_bytes, stats.network.tx_bytes)
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
        <TitledGreyBox css={tw`break-words`} title={name} icon={faServer}>
            <p css={tw`text-xs uppercase`}>
                <FontAwesomeIcon
                    icon={faCircle}
                    fixedWidth
                    css={[
                        tw`mr-1`,
                        statusToColor(status, isInstalling || isTransferring),
                    ]}
                />
                &nbsp;{!status ? 'Connecting...' : (isInstalling ? 'Installing' : (isTransferring) ? 'Transferring' : status)}
                {stats.uptime > 0 &&
                <span css={tw`ml-2 lowercase`}>
                    (<UptimeDuration uptime={stats.uptime / 1000}/>)
                </span>
                }
            </p>
            <CopyOnClick text={primaryAllocation}>
                <p css={tw`text-xs mt-2`}>
                    <FontAwesomeIcon icon={faEthernet} fixedWidth css={tw`mr-1`}/>
                    <code css={tw`ml-1`}>{primaryAllocation}</code>
                </p>
            </CopyOnClick>
            <p css={tw`text-xs mt-2`}>
                <FontAwesomeIcon icon={faMicrochip} fixedWidth css={tw`mr-1`}/> {stats.cpu.toFixed(2)}%
                <span css={tw`text-neutral-500`}> / {cpuLimit}</span>
            </p>
            <p css={tw`text-xs mt-2`}>
                <FontAwesomeIcon icon={faMemory} fixedWidth css={tw`mr-1`}/> {bytesToHuman(stats.memory)}
                <span css={tw`text-neutral-500`}> / {memoryLimit}</span>
            </p>
            <p css={tw`text-xs mt-2`}>
                <FontAwesomeIcon icon={faHdd} fixedWidth css={tw`mr-1`}/>&nbsp;{bytesToHuman(stats.disk)}
                <span css={tw`text-neutral-500`}> / {diskLimit}</span>
            </p>
            <p css={tw`text-xs mt-2`}>
                <FontAwesomeIcon icon={faNetworkWired} fixedWidth css={tw`mr-1`}/>&nbsp;{bpsToHuman(stats.networkDataRate)}
            </p>
        </TitledGreyBox>
    );
};

export default ServerDetailsBlock;
