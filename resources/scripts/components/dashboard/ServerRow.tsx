import React, { useEffect, useRef, useState } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faEthernet, faHdd, faMemory, faMicrochip, faServer } from '@fortawesome/free-solid-svg-icons';
import { Link } from 'react-router-dom';
import { Server } from '@/api/server/getServer';
import getServerResourceUsage, { ServerStats } from '@/api/server/getServerResourceUsage';
import { bytesToHuman, megabytesToHuman } from '@/helpers';
import tw from 'twin.macro';
import GreyRowBox from '@/components/elements/GreyRowBox';
import Spinner from '@/components/elements/Spinner';

// Determines if the current value is in an alarm threshold so we can show it in red rather
// than the more faded default style.
const isAlarmState = (current: number, limit: number): boolean => {
    const limitInBytes = limit * 1024 * 1024;

    return current / limitInBytes >= 0.90;
};

export default ({ server, className }: { server: Server; className?: string }) => {
    const interval = useRef<number>(null);
    const [ stats, setStats ] = useState<ServerStats | null>(null);
    const [ statsError, setStatsError ] = useState(false);

    const getStats = () => {
        setStatsError(false);
        return getServerResourceUsage(server.uuid)
            .then(data => setStats(data))
            .catch(error => {
                setStatsError(true);
                console.error(error);
            });
    };

    useEffect(() => {
        getStats().then(() => {
            // @ts-ignore
            interval.current = setInterval(() => getStats(), 20000);
        });

        return () => {
            interval.current && clearInterval(interval.current);
        };
    }, []);

    const alarms = { cpu: false, memory: false, disk: false };
    if (stats) {
        alarms.cpu = server.limits.cpu === 0 ? false : (stats.cpuUsagePercent >= (server.limits.cpu * 0.9));
        alarms.memory = isAlarmState(stats.memoryUsageInBytes, server.limits.memory);
        alarms.disk = server.limits.disk === 0 ? false : isAlarmState(stats.diskUsageInBytes, server.limits.disk);
    }

    const disklimit = server.limits.disk !== 0 ? megabytesToHuman(server.limits.disk) : 'Unlimited';
    const memorylimit = server.limits.memory !== 0 ? megabytesToHuman(server.limits.memory) : 'Unlimited';

    return (
        <GreyRowBox as={Link} to={`/server/${server.id}`} className={className}>
            <div className={'icon'} css={tw`hidden md:block`}>
                <FontAwesomeIcon icon={faServer}/>
            </div>
            <div css={tw`flex-1 md:ml-4`}>
                <p css={tw`text-lg break-all`}>{server.name}</p>
                {!!server.description &&
                <p css={tw`text-sm text-neutral-300 break-all`}>{server.description}</p>
                }
            </div>
            <div css={tw`w-48 overflow-hidden self-start hidden lg:block`}>
                <div css={tw`flex ml-4 justify-end`}>
                    <FontAwesomeIcon icon={faEthernet} css={tw`text-neutral-500`}/>
                    <p css={tw`text-sm text-neutral-400 ml-2`}>
                        {
                            server.allocations.filter(alloc => alloc.isDefault).map(allocation => (
                                <span key={allocation.ip + allocation.port.toString()}>{allocation.alias || allocation.ip}:{allocation.port}</span>
                            ))
                        }
                    </p>
                </div>
            </div>
            <div css={tw`w-1/3 sm:w-1/2 lg:w-1/3 flex items-baseline justify-center relative`}>
                {!stats ?
                    !statsError ?
                        <Spinner size={'small'}/>
                        :
                        server.isInstalling ?
                            <div css={tw`flex-1 text-center`}>
                                <span css={tw`bg-neutral-500 rounded px-2 py-1 text-neutral-100 text-xs`}>
                                    Installing
                                </span>
                            </div>
                            :
                            <div css={tw`flex-1 text-center`}>
                                <span css={tw`bg-red-500 rounded px-2 py-1 text-red-100 text-xs`}>
                                    {server.isSuspended ? 'Suspended' : 'Connection Error'}
                                </span>
                            </div>
                    :
                    <React.Fragment>
                        <div css={tw`flex-1 flex md:ml-4 sm:flex hidden justify-center`}>
                            <FontAwesomeIcon
                                icon={faMicrochip}
                                css={[
                                    !alarms.cpu && tw`text-neutral-500`,
                                    alarms.cpu && tw`text-red-400`,
                                ]}
                            />
                            <p
                                css={[
                                    tw`text-sm ml-2`,
                                    !alarms.cpu && tw`text-neutral-400`,
                                    alarms.cpu && tw`text-white`,
                                ]}
                            >
                                {stats.cpuUsagePercent} %
                            </p>
                        </div>
                        <div css={tw`flex-1 ml-4 sm:block hidden`}>
                            <div css={tw`flex justify-center`}>
                                <FontAwesomeIcon
                                    icon={faMemory}
                                    css={[
                                        !alarms.memory && tw`text-neutral-500`,
                                        alarms.memory && tw`text-red-400`,
                                    ]}
                                />
                                <p
                                    css={[
                                        tw`text-sm ml-2`,
                                        !alarms.memory && tw`text-neutral-400`,
                                        alarms.memory && tw`text-white`,
                                    ]}
                                >
                                    {bytesToHuman(stats.memoryUsageInBytes)}
                                </p>
                            </div>
                            <p css={tw`text-xs text-neutral-600 text-center mt-1`}>of {memorylimit}</p>
                        </div>
                        <div css={tw`flex-1 ml-4 sm:block hidden`}>
                            <div css={tw`flex justify-center`}>
                                <FontAwesomeIcon
                                    icon={faHdd}
                                    css={[
                                        !alarms.disk && tw`text-neutral-500`,
                                        alarms.disk && tw`text-red-400`,
                                    ]}
                                />
                                <p
                                    css={[
                                        tw`text-sm ml-2`,
                                        !alarms.disk && tw`text-neutral-400`,
                                        alarms.disk && tw`text-white`,
                                    ]}
                                >
                                    {bytesToHuman(stats.diskUsageInBytes)}
                                </p>
                            </div>
                            <p css={tw`text-xs text-neutral-600 text-center mt-1`}>of {disklimit}</p>
                        </div>
                        <div css={tw`flex-1 flex justify-end sm:hidden`}>
                            <div css={tw`flex items-end text-right`}>
                                <div
                                    css={[
                                        tw`w-3 h-3 rounded-full`,
                                        (!stats?.status || stats?.status === 'offline')
                                            ? tw`bg-red-500`
                                            : (stats?.status === 'running' ? tw`bg-green-500` : tw`bg-yellow-500`),
                                    ]}
                                />
                            </div>
                        </div>
                    </React.Fragment>
                }
            </div>
        </GreyRowBox>
    );
};
