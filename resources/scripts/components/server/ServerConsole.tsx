import React, { lazy, useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faServer } from '@fortawesome/free-solid-svg-icons/faServer';
import { faCircle } from '@fortawesome/free-solid-svg-icons/faCircle';
import classNames from 'classnames';
import styled from 'styled-components';
import { faMemory } from '@fortawesome/free-solid-svg-icons/faMemory';
import { faMicrochip } from '@fortawesome/free-solid-svg-icons/faMicrochip';
import { bytesToHuman } from '@/helpers';
import Spinner from '@/components/elements/Spinner';

const GreyBox = styled.div`
    ${tw`mt-4 shadow-md bg-neutral-700 rounded p-3 flex text-xs`}   
`;

const ChunkedConsole = lazy(() => import('@/components/server/Console'));

export default () => {
    const [ memory, setMemory ] = useState(0);
    const [ cpu, setCpu ] = useState(0);

    const server = ServerContext.useStoreState(state => state.server.data!);
    const status = ServerContext.useStoreState(state => state.status.value);

    const { connected, instance } = ServerContext.useStoreState(state => state.socket);

    const statsListener = (data: string) => {
        let stats: any = {};
        try {
            stats = JSON.parse(data);
        } catch (e) {
            return;
        }

        setMemory(stats.memory_bytes);
        setCpu(stats.cpu_absolute);
    };

    const sendPowerCommand = (command: 'start' | 'stop' | 'restart' | 'kill') => {
        instance && instance.send('set state', command);
    };

    useEffect(() => {
        if (!connected || !instance) {
            return;
        }

        instance.addListener('stats', statsListener);

        return () => {
            instance.removeListener('stats', statsListener);
        };
    }, [ connected ]);

    return (
        <div className={'my-10 flex'}>
            <div className={'flex-1 ml-4'}>
                <div className={'rounded shadow-md bg-neutral-700'}>
                    <div className={'bg-neutral-900 rounded-t p-3 border-b border-black'}>
                        <p className={'text-sm uppercase'}>
                            <FontAwesomeIcon icon={faServer} className={'mr-1 text-neutral-300'}/> {server.name}
                        </p>
                    </div>
                    <div className={'p-3'}>
                        <p className={'text-xs uppercase'}>
                            <FontAwesomeIcon
                                icon={faCircle}
                                fixedWidth={true}
                                className={classNames('mr-1', {
                                    'text-red-500': status === 'offline',
                                    'text-yellow-500': [ 'running', 'offline' ].indexOf(status) < 0,
                                    'text-green-500': status === 'running',
                                })}
                            />
                            &nbsp;{status}
                        </p>
                        <p className={'text-xs mt-2'}>
                            <FontAwesomeIcon
                                icon={faMemory}
                                fixedWidth={true}
                                className={'mr-1'}
                            />
                            &nbsp;{bytesToHuman(memory)}
                            <span className={'text-neutral-500'}>/ {server.limits.memory} MB</span>
                        </p>
                        <p className={'text-xs mt-2'}>
                            <FontAwesomeIcon
                                icon={faMicrochip}
                                fixedWidth={true}
                                className={'mr-1'}
                            />
                            &nbsp;{cpu.toFixed(2)} %
                        </p>
                    </div>
                </div>
                <GreyBox className={'justify-center'}>
                    <button
                        className={'btn btn-secondary btn-xs mr-2'}
                        disabled={status !== 'offline'}
                        onClick={e => {
                            e.preventDefault();
                            sendPowerCommand('start');
                        }}
                    >
                        Start
                    </button>
                    <button
                        className={'btn btn-secondary btn-xs mr-2'}
                        onClick={e => {
                            e.preventDefault();
                            sendPowerCommand('restart');
                        }}
                    >
                        Restart
                    </button>
                    <button
                        className={'btn btn-red btn-xs'}
                        disabled={status === 'offline'}
                        onClick={e => {
                            e.preventDefault();
                            sendPowerCommand(status === 'stopping' ? 'kill' : 'stop');
                        }}
                    >
                        Stop
                    </button>
                </GreyBox>
            </div>
            <React.Suspense
                fallback={
                    <div className={'mx-4 w-3/4 mr-4 flex items-center justify-center'}>
                        <Spinner centered={true} size={'normal'}/>
                    </div>
                }
            >
                <div className={'mx-4 w-3/4 mr-4'}>
                    <ChunkedConsole/>
                </div>
            </React.Suspense>
        </div>
    );
};
