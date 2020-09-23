import React, { lazy, useEffect, useState } from 'react';
import { ServerContext } from '@/state/server';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCircle, faHdd, faMemory, faMicrochip, faServer } from '@fortawesome/free-solid-svg-icons';
import { bytesToHuman, megabytesToHuman } from '@/helpers';
import SuspenseSpinner from '@/components/elements/SuspenseSpinner';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Can from '@/components/elements/Can';
import ContentContainer from '@/components/elements/ContentContainer';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import StopOrKillButton from '@/components/server/StopOrKillButton';
import ServerContentBlock from '@/components/elements/ServerContentBlock';

export type PowerAction = 'start' | 'stop' | 'restart' | 'kill';

const ChunkedConsole = lazy(() => import(/* webpackChunkName: "console" */'@/components/server/Console'));
const ChunkedStatGraphs = lazy(() => import(/* webpackChunkName: "graphs" */'@/components/server/StatGraphs'));

export default () => {
    const [ memory, setMemory ] = useState(0);
    const [ cpu, setCpu ] = useState(0);
    const [ disk, setDisk ] = useState(0);

    const name = ServerContext.useStoreState(state => state.server.data!.name);
    const limits = ServerContext.useStoreState(state => state.server.data!.limits);
    const isInstalling = ServerContext.useStoreState(state => state.server.data!.isInstalling);
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

        setMemory(stats.memory_bytes);
        setCpu(stats.cpu_absolute);
        setDisk(stats.disk_bytes);
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

    const disklimit = limits.disk ? megabytesToHuman(limits.disk) : 'Unlimited';
    const memorylimit = limits.memory ? megabytesToHuman(limits.memory) : 'Unlimited';

    return (
        <ServerContentBlock title={'Console'} css={tw`flex flex-wrap`}>
            <div css={tw`w-full md:w-1/4`}>
                <TitledGreyBox css={tw`break-all`} title={name} icon={faServer}>
                    <p css={tw`text-xs uppercase`}>
                        <FontAwesomeIcon
                            icon={faCircle}
                            fixedWidth
                            css={[
                                tw`mr-1`,
                                status === 'offline' ? tw`text-red-500` : (status === 'running' ? tw`text-green-500` : tw`text-yellow-500`),
                            ]}
                        />
                        &nbsp;{!status ? 'Connecting...' : status}
                    </p>
                    <p css={tw`text-xs mt-2`}>
                        <FontAwesomeIcon icon={faMicrochip} fixedWidth css={tw`mr-1`}/> {cpu.toFixed(2)}%
                    </p>
                    <p css={tw`text-xs mt-2`}>
                        <FontAwesomeIcon icon={faMemory} fixedWidth css={tw`mr-1`}/> {bytesToHuman(memory)}
                        <span css={tw`text-neutral-500`}> / {memorylimit}</span>
                    </p>
                    <p css={tw`text-xs mt-2`}>
                        <FontAwesomeIcon icon={faHdd} fixedWidth css={tw`mr-1`}/>&nbsp;{bytesToHuman(disk)}
                        <span css={tw`text-neutral-500`}> / {disklimit}</span>
                    </p>
                </TitledGreyBox>
                {!isInstalling ?
                    <Can action={[ 'control.start', 'control.stop', 'control.restart' ]} matchAny>
                        <div css={tw`shadow-md bg-neutral-700 rounded p-3 flex text-xs mt-4 justify-center`}>
                            <Can action={'control.start'}>
                                <Button
                                    size={'xsmall'}
                                    color={'green'}
                                    isSecondary
                                    css={tw`mr-2`}
                                    disabled={status !== 'offline'}
                                    onClick={e => {
                                        e.preventDefault();
                                        sendPowerCommand('start');
                                    }}
                                >
                                    Start
                                </Button>
                            </Can>
                            <Can action={'control.restart'}>
                                <Button
                                    size={'xsmall'}
                                    isSecondary
                                    css={tw`mr-2`}
                                    disabled={!status}
                                    onClick={e => {
                                        e.preventDefault();
                                        sendPowerCommand('restart');
                                    }}
                                >
                                    Restart
                                </Button>
                            </Can>
                            <Can action={'control.stop'}>
                                <StopOrKillButton onPress={action => sendPowerCommand(action)}/>
                            </Can>
                        </div>
                    </Can>
                    :
                    <div css={tw`mt-4 rounded bg-yellow-500 p-3`}>
                        <ContentContainer>
                            <p css={tw`text-sm text-yellow-900`}>
                                This server is currently running its installation process and most actions are
                                unavailable.
                            </p>
                        </ContentContainer>
                    </div>
                }
            </div>
            <div css={tw`w-full md:flex-1 md:ml-4 mt-4 md:mt-0`}>
                <SuspenseSpinner>
                    <ChunkedConsole/>
                    <ChunkedStatGraphs/>
                </SuspenseSpinner>
            </div>
        </ServerContentBlock>
    );
};
