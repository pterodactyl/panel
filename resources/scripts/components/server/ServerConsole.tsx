import React, { memo } from 'react';
import { ServerContext } from '@/state/server';
import Can from '@/components/elements/Can';
import ContentContainer from '@/components/elements/ContentContainer';
import tw from 'twin.macro';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import ServerDetailsBlock from '@/components/server/ServerDetailsBlock';
import isEqual from 'react-fast-compare';
import PowerControls from '@/components/server/PowerControls';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import Spinner from '@/components/elements/Spinner';
import Features from '@feature/Features';
import Console from '@/components/server/Console';
import StatGraphs from '@/components/server/StatGraphs';

export type PowerAction = 'start' | 'stop' | 'restart' | 'kill';

const ServerConsole = () => {
    const isInstalling = ServerContext.useStoreState(state => state.server.data!.isInstalling);
    const isTransferring = ServerContext.useStoreState(state => state.server.data!.isTransferring);
    const eggFeatures = ServerContext.useStoreState(state => state.server.data!.eggFeatures, isEqual);

    return (
        <ServerContentBlock title={'服务器控制台'} css={tw`flex flex-wrap`}>
            <div css={tw`w-full lg:w-1/4`}>
                <ServerDetailsBlock/>
                {isInstalling ?
                    <div css={tw`mt-4 rounded bg-yellow-500 p-3`}>
                        <ContentContainer>
                            <p css={tw`text-sm text-yellow-900`}>
                                此服务器实例当前正在运行其安装过程，大多数操作都是
                                不可用的。
                            </p>
                        </ContentContainer>
                    </div>
                    :
                    isTransferring ?
                        <div css={tw`mt-4 rounded bg-yellow-500 p-3`}>
                            <ContentContainer>
                                <p css={tw`text-sm text-yellow-900`}>
                                    此服务器实例当前正在转移到另一个节点服务器，所有操作
                                    都不可用。
                                </p>
                            </ContentContainer>
                        </div>
                        :
                        <Can action={[ 'control.start', 'control.stop', 'control.restart' ]} matchAny>
                            <PowerControls/>
                        </Can>
                }
            </div>
            <div css={tw`w-full lg:w-3/4 mt-4 lg:mt-0 lg:pl-4`}>
                <Spinner.Suspense>
                    <ErrorBoundary>
                        <Console/>
                    </ErrorBoundary>
                    <StatGraphs/>
                </Spinner.Suspense>
                <Features enabled={eggFeatures} />
            </div>
        </ServerContentBlock>
    );
};

export default memo(ServerConsole, isEqual);
