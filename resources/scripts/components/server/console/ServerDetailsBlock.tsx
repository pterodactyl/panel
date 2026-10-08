import React, { useEffect, useState } from 'react';
import { Clock, CloudDownload, CloudUpload, HardDrive, MemoryStick, Cpu, Wifi } from 'lucide-react';
import { bytesToString, ip, mbToBytes } from '@/lib/formatters';
import { useServerStatus, useSocketConnected, useSocketInstance, type ServerStatus } from '@/state/server';
import { SocketEvent, SocketRequest } from '@/components/server/events';
import UptimeDuration from '@/components/server/UptimeDuration';
import StatBlock, { type StatBlockTone } from '@/components/server/console/StatBlock';
import useWebsocketEvent from '@/plugins/useWebsocketEvent';
import { cn } from '@/lib/cn';
import { capitalize } from '@/lib/strings';
import { useCurrentServer } from '@/api/server/queries';
import { relationshipData } from '@/api/relationships';
import { parseServerStatsPayload } from '@/components/server/console/stats';

type Stats = Record<'memory' | 'cpu' | 'disk' | 'uptime' | 'rx' | 'tx', number>;

const getTone = (value: number, max: number | null): StatBlockTone | undefined => {
    const delta = max ? value / max : 0;

    if (delta > 0.8) {
        if (delta > 0.9) {
            return 'destructive';
        }

        return 'warning';
    }

    return undefined;
};

const getUptimeTone = (status: ServerStatus): StatBlockTone | undefined => {
    if (status === 'running') {
        return undefined;
    }

    return status === 'offline' ? 'destructive' : 'warning';
};

const Uptime = ({ status, uptime }: { status: ServerStatus; uptime: number }) => {
    if (status === null) {
        return 'Offline';
    }

    if (uptime > 0) {
        return <UptimeDuration uptime={uptime / 1000} />;
    }

    return capitalize(status);
};

const Limit = ({ limit, children }: { limit: string | null; children: React.ReactNode }) => (
    <>
        {children}
        <span className='ml-1 text-xs text-muted-foreground select-none'>/ {limit || <>&infin;</>}</span>
    </>
);

const ServerDetailsBlock = ({ className }: { className?: string }) => {
    const [stats, setStats] = useState<Stats>({ memory: 0, cpu: 0, disk: 0, uptime: 0, tx: 0, rx: 0 });

    const status = useServerStatus();
    const connected = useSocketConnected();
    const instance = useSocketInstance();
    const server = useCurrentServer()!;
    const limits = server.attributes.limits;

    const textLimits = {
        cpu: limits?.cpu ? `${limits.cpu}%` : null,
        memory: limits?.memory ? bytesToString(mbToBytes(limits.memory)) : null,
        disk: limits?.disk ? bytesToString(mbToBytes(limits.disk)) : null,
    };
    const defaultAllocation = relationshipData(server.attributes.relationships?.allocations).find(
        (allocation) => allocation.attributes.is_default
    );
    const allocation = defaultAllocation
        ? `${defaultAllocation.attributes.ip_alias || ip(defaultAllocation.attributes.ip)}:${defaultAllocation.attributes.port}`
        : 'n/a';

    useEffect(() => {
        if (!connected || !instance) {
            return;
        }

        instance.send(SocketRequest.SEND_STATS);
    }, [instance, connected]);

    useWebsocketEvent(SocketEvent.STATS, (data) => {
        const stats = parseServerStatsPayload(data);

        if (!stats) {
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
        <div className={cn('grid grid-cols-6 gap-2 md:gap-4', className)}>
            <StatBlock icon={Wifi} title='Address' copyOnClick={allocation}>
                {allocation}
            </StatBlock>
            <StatBlock icon={Clock} title='Uptime' tone={getUptimeTone(status)}>
                <Uptime status={status} uptime={stats.uptime} />
            </StatBlock>
            <StatBlock icon={Cpu} title='CPU Load' tone={getTone(stats.cpu, limits.cpu)}>
                {status === 'offline' ? (
                    <span className='text-muted-foreground'>Offline</span>
                ) : (
                    <Limit limit={textLimits.cpu}>{stats.cpu.toFixed(2)}%</Limit>
                )}
            </StatBlock>
            <StatBlock icon={MemoryStick} title='Memory' tone={getTone(stats.memory / 1024, limits.memory * 1024)}>
                {status === 'offline' ? (
                    <span className='text-muted-foreground'>Offline</span>
                ) : (
                    <Limit limit={textLimits.memory}>{bytesToString(stats.memory)}</Limit>
                )}
            </StatBlock>
            <StatBlock icon={HardDrive} title='Disk' tone={getTone(stats.disk / 1024, limits.disk * 1024)}>
                <Limit limit={textLimits.disk}>{bytesToString(stats.disk)}</Limit>
            </StatBlock>
            <StatBlock icon={CloudDownload} title='Network (Inbound)'>
                {status === 'offline' ? (
                    <span className='text-muted-foreground'>Offline</span>
                ) : (
                    bytesToString(stats.rx)
                )}
            </StatBlock>
            <StatBlock icon={CloudUpload} title='Network (Outbound)'>
                {status === 'offline' ? (
                    <span className='text-muted-foreground'>Offline</span>
                ) : (
                    bytesToString(stats.tx)
                )}
            </StatBlock>
        </div>
    );
};

export default ServerDetailsBlock;
