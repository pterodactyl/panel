import React, { useEffect, useState } from 'react';
import {
    faClock,
    faCloudDownloadAlt,
    faCloudUploadAlt,
    faHdd,
    faMemory,
    faMicrochip,
    faWifi,
} from '@fortawesome/free-solid-svg-icons';
import { bytesToString, ip, mbToBytes } from '@/lib/formatters';
import { ServerContext } from '@/state/server';
import { SocketEvent, SocketRequest } from '@/components/server/events';
import UptimeDuration from '@/components/server/UptimeDuration';
import StatBlock from '@/components/server/console/StatBlock';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import classNames from 'classnames';

type Stats = Record<'memory' | 'cpu' | 'disk' | 'uptime' | 'rx' | 'tx', number>;

const getBackgroundColor = (value: number, max: number | null): string | undefined => {
    const delta = !max ? 0 : value / max;

    if (delta > 0.8) {
        if (delta > 0.9) {
            return 'bg-red-500';
        }
        return 'bg-yellow-500';
    }

    return undefined;
};

const ServerDetailsBlock = ({ className }: { className?: string }) => {
    const [stats, setStats] = useState<Stats>({ memory: 0, cpu: 0, disk: 0, uptime: 0, tx: 0, rx: 0 });

    const status = ServerContext.useStoreState((state) => state.status.value);
    const connected = ServerContext.useStoreState((state) => state.socket.connected);
    const instance = ServerContext.useStoreState((state) => state.socket.instance);
    const limits = ServerContext.useStoreState((state) => state.server.data!.limits);
    const allocation = ServerContext.useStoreState((state) => {
        const match = state.server.data!.allocations.find((allocation) => allocation.isDefault);

        return !match ? 'n/a' : `${match.alias || ip(match.ip)}:${match.port}`;
    });

    useEffect(() => {
        if (!connected || !instance) {
            return;
        }

        instance.send(SocketRequest.SEND_STATS);
    }, [instance, connected]);

    useWebsocketEvent(SocketEvent.STATS, (data) => {
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
    });

    return (
        <div className={classNames('grid grid-cols-6 gap-2 md:gap-4', className)}>
            <StatBlock icon={faWifi} title={'Address'}>
                {allocation}
            </StatBlock>
            <StatBlock
                icon={faClock}
                title={'Uptime'}
                color={getBackgroundColor(status === 'running' ? 0 : status !== 'offline' ? 9 : 10, 10)}
            >
                {stats.uptime > 0 ? <UptimeDuration uptime={stats.uptime / 1000} /> : 'Offline'}
            </StatBlock>
            <StatBlock
                icon={faMicrochip}
                title={'CPU Load'}
                color={getBackgroundColor(stats.cpu, limits.cpu)}
                description={
                    limits.cpu
                        ? `This server is allowed to use up to ${limits.cpu}% of the host's available CPU resources.`
                        : 'No CPU limit has been configured for this server.'
                }
            >
                {status === 'offline' ? <span className={'text-gray-400'}>Offline</span> : `${stats.cpu.toFixed(2)}%`}
            </StatBlock>
            <StatBlock
                icon={faMemory}
                title={'Memory'}
                color={getBackgroundColor(stats.memory / 1024, limits.memory * 1024)}
                description={
                    limits.memory
                        ? `This server is allowed to use up to ${bytesToString(mbToBytes(limits.memory))} of memory.`
                        : 'No memory limit has been configured for this server.'
                }
            >
                {status === 'offline' ? <span className={'text-gray-400'}>Offline</span> : bytesToString(stats.memory)}
            </StatBlock>
            <StatBlock
                icon={faHdd}
                title={'Disk'}
                color={getBackgroundColor(stats.disk / 1024, limits.disk * 1024)}
                description={
                    limits.disk
                        ? `This server is allowed to use up to ${bytesToString(mbToBytes(limits.disk))} of disk space.`
                        : 'No disk space limit has been configured for this server.'
                }
            >
                {bytesToString(stats.disk)}
            </StatBlock>
            <StatBlock
                icon={faCloudDownloadAlt}
                title={'Network (Inbound)'}
                description={'The total amount of network traffic that your server has recieved since it was started.'}
            >
                {status === 'offline' ? <span className={'text-gray-400'}>Offline</span> : bytesToString(stats.tx)}
            </StatBlock>
            <StatBlock
                icon={faCloudUploadAlt}
                title={'Network (Outbound)'}
                description={
                    'The total amount of traffic your server has sent across the internet since it was started.'
                }
            >
                {status === 'offline' ? <span className={'text-gray-400'}>Offline</span> : bytesToString(stats.rx)}
            </StatBlock>
        </div>
    );
};

export default ServerDetailsBlock;
