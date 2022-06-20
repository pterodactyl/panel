import React, { memo } from 'react';
import { ServerContext } from '@/state/server';
import Can from '@/components/elements/Can';
import ContentContainer from '@/components/elements/ContentContainer';
import tw from 'twin.macro';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import isEqual from 'react-fast-compare';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import Spinner from '@/components/elements/Spinner';
import Features from '@feature/Features';
import Console from '@/components/server/Console';
import StatGraphs from '@/components/server/StatGraphs';
import PowerButtons from '@/components/server/console/PowerButtons';
import ServerDetailsBlock from '@/components/server/ServerDetailsBlock';

export type PowerAction = 'start' | 'stop' | 'restart' | 'kill';

const ServerConsole = () => {
    const name = ServerContext.useStoreState(state => state.server.data!.name);
    const description = ServerContext.useStoreState(state => state.server.data!.description);
    const isInstalling = ServerContext.useStoreState(state => state.server.data!.isInstalling);
    const isTransferring = ServerContext.useStoreState(state => state.server.data!.isTransferring);
    const eggFeatures = ServerContext.useStoreState(state => state.server.data!.eggFeatures, isEqual);

    return (
        <ServerContentBlock title={'Console'} className={'grid grid-cols-4 gap-4'}>
            <div className={'flex space-x-4 items-end col-span-4'}>
                <div className={'flex-1'}>
                    <h1 className={'font-header text-2xl text-gray-50 leading-relaxed line-clamp-1'}>{name}</h1>
                    <p className={'text-sm line-clamp-2'}>{description}</p>
                </div>
                <div className={'flex-1'}>
                    <Can action={[ 'control.start', 'control.stop', 'control.restart' ]} matchAny>
                        <PowerButtons className={'flex justify-end space-x-2'}/>
                    </Can>
                </div>
            </div>
            <div className={'col-span-4 lg:col-span-1'}>
                <ServerDetailsBlock className={'flex flex-col space-y-4'}/>
                {isInstalling ?
                    <div css={tw`mt-4 rounded bg-yellow-500 p-3`}>
                        <ContentContainer>
                            <p css={tw`text-sm text-yellow-900`}>
                                This server is currently running its installation process and most actions are
                                unavailable.
                            </p>
                        </ContentContainer>
                    </div>
                    :
                    isTransferring ?
                        <div css={tw`mt-4 rounded bg-yellow-500 p-3`}>
                            <ContentContainer>
                                <p css={tw`text-sm text-yellow-900`}>
                                    This server is currently being transferred to another node and all actions
                                    are unavailable.
                                </p>
                            </ContentContainer>
                        </div>
                        :
                        null
                }
            </div>
            <div className={'col-span-3'}>
                <Spinner.Suspense>
                    <ErrorBoundary>
                        <Console/>
                    </ErrorBoundary>
                    <StatGraphs/>
                </Spinner.Suspense>
                <Features enabled={eggFeatures}/>
            </div>
        </ServerContentBlock>
    );
};

export default memo(ServerConsole, isEqual);
