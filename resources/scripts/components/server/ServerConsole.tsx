import React, { lazy, useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faServer } from '@fortawesome/free-solid-svg-icons/faServer';
import { faCircle } from '@fortawesome/free-solid-svg-icons/faCircle';
import classNames from 'classnames';
import { faMemory } from '@fortawesome/free-solid-svg-icons/faMemory';
import { faMicrochip } from '@fortawesome/free-solid-svg-icons/faMicrochip';
import { bytesToHuman } from '@/helpers';
import SuspenseSpinner from '@/components/elements/SuspenseSpinner';
import TitledGreyBox from '@/components/elements/TitledGreyBox';

type PowerAction = 'start' | 'stop' | 'restart' | 'kill';

const ChunkedConsole = lazy(() => import(/* webpackChunkName: "console" */'@/components/server/Console'));
const ChunkedStatGraphs = lazy(() => import(/* webpackChunkName: "graphs" */'@/components/server/StatGraphs'));

const StopOrKillButton = ({ onPress }: { onPress: (action: PowerAction) => void }) => {
    const [ clicked, setClicked ] = useState(false);
    const status = ServerContext.useStoreState(state => state.status.value);

    useEffect(() => {
        setClicked(state => [ 'stopping' ].indexOf(status) < 0 ? false : state);
    }, [ status ]);

    return (
        <button
            className={'btn btn-red btn-xs'}
            disabled={status === 'offline'}
            onClick={e => {
                e.preventDefault();
                onPress(clicked ? 'kill' : 'stop');
                setClicked(true);
            }}
        >
            {clicked ? 'Kill' : 'Stop'}
        </button>
    );
};

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

    const sendPowerCommand = (command: PowerAction) => {
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
    }, [ instance, connected ]);

    return (
        <div className={'my-10 flex'}>
            <div className={'w-1/4'}>
                <TitledGreyBox title={server.name} icon={faServer}>
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
                </TitledGreyBox>
                <div className={'grey-box justify-center'}>
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
                    <StopOrKillButton onPress={action => sendPowerCommand(action)}/>
                </div>
            </div>
            <div className={'flex-1 mx-4 mr-4'}>
                <SuspenseSpinner>
                    <ChunkedConsole/>
                    <ChunkedStatGraphs/>
                </SuspenseSpinner>
            </div>
        </div>
    );
};
