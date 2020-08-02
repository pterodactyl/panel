import React, { lazy, useEffect, useState } from 'react';
import { Helmet } from 'react-helmet';
import { ServerContext } from '@/state/server';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCircle, faHdd, faMemory, faMicrochip, faServer } from '@fortawesome/free-solid-svg-icons';
import { bytesToHuman, megabytesToHuman } from '@/helpers';
import SuspenseSpinner from '@/components/elements/SuspenseSpinner';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import Can from '@/components/elements/Can';
import PageContentBlock from '@/components/elements/PageContentBlock';
import ContentContainer from '@/components/elements/ContentContainer';
import tw from 'twin.macro';
import Button from '@/components/elements/Button';
import StopOrKillButton from '@/components/server/StopOrKillButton';

export type PowerAction = 'start' | 'stop' | 'restart' | 'kill';

const ChunkedConsole = lazy(() => import(/* webpackChunkName: "console" */'@/components/server/Console'));
const ChunkedStatGraphs = lazy(() => import(/* webpackChunkName: "graphs" */'@/components/server/StatGraphs'));

export default () => {
    const [ memory, setMemory ] = useState(0);
    const [ cpu, setCpu ] = useState(0);
    const [ disk, setDisk ] = useState(0);

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

    const disklimit = server.limits.disk ? megabytesToHuman(server.limits.disk) : 'Unlimited';
    const memorylimit = server.limits.memory ? megabytesToHuman(server.limits.memory) : 'Unlimited';

    return (
        <PageContentBlock css={tw`flex`}>
            <Helmet>
                <title> {server.name} | Console </title>
            </Helmet>
            <div css={tw`w-1/4`}>
                <TitledGreyBox title={server.name} icon={faServer}>
                    <p css={tw`text-xs uppercase`}>
                        <FontAwesomeIcon
                            icon={faCircle}
                            fixedWidth
                            css={[
                                tw`mr-1`,
                                status === 'offline' ? tw`text-red-500` : (status === 'running' ? tw`text-green-500` : tw`text-yellow-500`),
                            ]}
                        />
                        &nbsp;{status}
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
                {!server.isInstalling ?
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
            <div css={tw`flex-1 ml-4`}>
                <SuspenseSpinner>
                    <ChunkedConsole/>
                    <ChunkedStatGraphs/>
                </SuspenseSpinner>
            </div>
        </PageContentBlock>
    );
};
