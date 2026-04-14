import React, { memo, useEffect, useRef, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faEthernet, faHdd, faMemory, faMicrochip, faServer } from '@fortawesome/free-solid-svg-icons';
import { Link } from 'react-router-dom';
import { Server } from '@/api/server/getServer';
import getServerResourceUsage, { ServerPowerState, ServerStats } from '@/api/server/getServerResourceUsage';
import { bytesToString, ip, mbToBytes } from '@/lib/formatters';
import GreyRowBox from '@/components/elements/GreyRowBox';
import Spinner from '@/components/elements/Spinner';
import styled from 'styled-components/macro';
import isEqual from 'react-fast-compare';

// Determines if the current value is in an alarm threshold so we can show it in red rather
// than the more faded default style.
const isAlarmState = (current: number, limit: number): boolean => limit > 0 && current / (limit * 1024 * 1024) >= 0.9;

const Icon = memo(
    styled(FontAwesomeIcon)<{ $alarm: boolean }>`
        ${(props) => (props.$alarm ? `color: #f87171;` : `color: hsl(211, 12%, 43%);`)};
    `,
    isEqual
);

const IconDescription = styled.p<{ $alarm: boolean }>`
    font-size: 0.875rem;
    line-height: 1.25rem;
    margin-left: 0.5rem;
    ${(props) => (props.$alarm ? `color: #ffffff;` : `color: hsl(211, 10%, 53%);`)};
`;

const StatusIndicatorBox = styled(GreyRowBox)<{ $status: ServerPowerState | undefined }>`
    display: grid;
    grid-template-columns: repeat(12, minmax(0, 1fr));
    gap: 1rem;
    position: relative;

    & .status-bar {
        width: 0.5rem;
        background-color: #ef4444;
        position: absolute;
        right: 0px;
        z-index: 20;
        border-radius: 9999px;
        margin: 0.25rem;
        opacity: 0.5;
        transition-property: all;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 150ms;
        height: calc(100% - 0.5rem);

        ${({ $status }) =>
            !$status || $status === 'offline'
                ? `background-color: #ef4444;`
                : $status === 'running'
                ? `background-color: #22c55e;`
                : `background-color: #eab308;`};
    }

    &:hover .status-bar {
        opacity: 0.75;
    }
`;

type Timer = ReturnType<typeof setInterval>;

export default ({ server, className }: { server: Server; className?: string }) => {
    const interval = useRef<Timer>(null) as React.MutableRefObject<Timer>;
    const [isSuspended, setIsSuspended] = useState(server.status === 'suspended');
    const [stats, setStats] = useState<ServerStats | null>(null);

    const getStats = () =>
        getServerResourceUsage(server.uuid)
            .then((data) => setStats(data))
            .catch((error) => console.error(error));

    useEffect(() => {
        setIsSuspended(stats?.isSuspended || server.status === 'suspended');
    }, [stats?.isSuspended, server.status]);

    useEffect(() => {
        // Don't waste a HTTP request if there is nothing important to show to the user because
        // the server is suspended.
        if (isSuspended) return;

        getStats().then(() => {
            interval.current = setInterval(() => getStats(), 30000);
        });

        return () => {
            interval.current && clearInterval(interval.current);
        };
    }, [isSuspended]);

    const alarms = { cpu: false, memory: false, disk: false };
    if (stats) {
        alarms.cpu = server.limits.cpu === 0 ? false : stats.cpuUsagePercent >= server.limits.cpu * 0.9;
        alarms.memory = isAlarmState(stats.memoryUsageInBytes, server.limits.memory);
        alarms.disk = server.limits.disk === 0 ? false : isAlarmState(stats.diskUsageInBytes, server.limits.disk);
    }

    const diskLimit = server.limits.disk !== 0 ? bytesToString(mbToBytes(server.limits.disk)) : 'Unlimited';
    const memoryLimit = server.limits.memory !== 0 ? bytesToString(mbToBytes(server.limits.memory)) : 'Unlimited';
    const cpuLimit = server.limits.cpu !== 0 ? server.limits.cpu + ' %' : 'Unlimited';

    return (
        <StatusIndicatorBox as={Link} to={`/server/${server.id}`} className={className} $status={stats?.status}>
            <div className={'flex items-center col-span-12 sm:col-span-5 lg:col-span-6'}>
                <div className={'icon mr-4'}>
                    <FontAwesomeIcon icon={faServer} />
                </div>
                <div>
                    <p className={'text-lg break-words'}>{server.name}</p>
                    {!!server.description && (
                        <p className={'text-sm text-neutral-300 break-words line-clamp-2'}>{server.description}</p>
                    )}
                </div>
            </div>
            <div className={'flex-1 ml-4 lg:block lg:col-span-2 hidden'}>
                <div className={'flex justify-center'}>
                    <FontAwesomeIcon icon={faEthernet} className={'text-neutral-500'} />
                    <p className={'text-sm text-neutral-400 ml-2'}>
                        {server.allocations
                            .filter((alloc) => alloc.isDefault)
                            .map((allocation) => (
                                <React.Fragment key={allocation.ip + allocation.port.toString()}>
                                    {allocation.alias || ip(allocation.ip)}:{allocation.port}
                                </React.Fragment>
                            ))}
                    </p>
                </div>
            </div>
            <div className={'hidden col-span-7 lg:col-span-4 sm:flex items-baseline justify-center'}>
                {!stats || isSuspended ? (
                    isSuspended ? (
                        <div className={'flex-1 text-center'}>
                            <span className={'bg-red-500 rounded px-2 py-1 text-red-100 text-xs'}>
                                {server.status === 'suspended' ? 'Suspended' : 'Connection Error'}
                            </span>
                        </div>
                    ) : server.isTransferring || server.status ? (
                        <div className={'flex-1 text-center'}>
                            <span className={'bg-neutral-500 rounded px-2 py-1 text-neutral-100 text-xs'}>
                                {server.isTransferring
                                    ? 'Transferring'
                                    : server.status === 'installing'
                                    ? 'Installing'
                                    : server.status === 'restoring_backup'
                                    ? 'Restoring Backup'
                                    : 'Unavailable'}
                            </span>
                        </div>
                    ) : (
                        <Spinner size={'small'} />
                    )
                ) : (
                    <React.Fragment>
                        <div className={'flex-1 ml-4 sm:block hidden'}>
                            <div className={'flex justify-center'}>
                                <Icon icon={faMicrochip} $alarm={alarms.cpu} />
                                <IconDescription $alarm={alarms.cpu}>
                                    {stats.cpuUsagePercent.toFixed(2)} %
                                </IconDescription>
                            </div>
                            <p className={'text-xs text-neutral-600 text-center mt-1'}>of {cpuLimit}</p>
                        </div>
                        <div className={'flex-1 ml-4 sm:block hidden'}>
                            <div className={'flex justify-center'}>
                                <Icon icon={faMemory} $alarm={alarms.memory} />
                                <IconDescription $alarm={alarms.memory}>
                                    {bytesToString(stats.memoryUsageInBytes)}
                                </IconDescription>
                            </div>
                            <p className={'text-xs text-neutral-600 text-center mt-1'}>of {memoryLimit}</p>
                        </div>
                        <div className={'flex-1 ml-4 sm:block hidden'}>
                            <div className={'flex justify-center'}>
                                <Icon icon={faHdd} $alarm={alarms.disk} />
                                <IconDescription $alarm={alarms.disk}>
                                    {bytesToString(stats.diskUsageInBytes)}
                                </IconDescription>
                            </div>
                            <p className={'text-xs text-neutral-600 text-center mt-1'}>of {diskLimit}</p>
                        </div>
                    </React.Fragment>
                )}
            </div>
            <div className={'status-bar'} />
        </StatusIndicatorBox>
    );
};
