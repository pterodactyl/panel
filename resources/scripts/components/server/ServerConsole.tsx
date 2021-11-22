import React, { lazy, memo } from 'react';
import { ServerContext } from '@/state/server';
import Can from '@/components/elements/Can';
import ContentContainer from '@/components/elements/ContentContainer';
import tw from 'twin.macro';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import ServerDetailsBlock from '@/components/server/ServerDetailsBlock';
import isEqual from 'react-fast-compare';
import PowerControls from '@/components/server/PowerControls';
import { EulaModalFeature, JavaVersionModalFeature } from '@feature/index';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import Spinner from '@/components/elements/Spinner';

export type PowerAction = 'start' | 'stop' | 'restart' | 'kill';

const ChunkedConsole = lazy(() => import(/* webpackChunkName: "console" */'@/components/server/Console'));
const ChunkedStatGraphs = lazy(() => import(/* webpackChunkName: "graphs" */'@/components/server/StatGraphs'));

const ServerConsole = () => {
    const isInstalling = ServerContext.useStoreState(state => state.server.data!.isInstalling);
    const isTransferring = ServerContext.useStoreState(state => state.server.data!.isTransferring);
    const eggFeatures = ServerContext.useStoreState(state => state.server.data!.eggFeatures, isEqual);
    const isSuspended = ServerContext.useStoreState(state => state.server.data!.status) === 'suspended';

    return (
        <ServerContentBlock title={'Console'} css={tw`flex flex-wrap`}>
            <div css={tw`w-full lg:w-1/4`}>
                <ServerDetailsBlock/>
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
                        isSuspended ?
                            <div css={tw`mt-4 rounded bg-red-500 p-3`}>
                                <ContentContainer css={tw`flex items-center justify-center bg-red-500`}>
                                    <div css={tw`text-sm text-red-900`}>
                                        <>
                                            <p css={tw`ml-2 text-sm text-red-100 bg-red-500 text-white`} style={{ fontWeight: 'lighter' }}>
                                                <div style={{ fontWeight: 'bold' }}>Server suspended</div> <br /> Your server is suspended please contact an administrator
                                            </p>
                                        </>
                                    </div>
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
                        <ChunkedConsole/>
                    </ErrorBoundary>
                    <ChunkedStatGraphs/>
                </Spinner.Suspense>
                <React.Suspense fallback={null}>
                    {eggFeatures.includes('eula') && <EulaModalFeature/>}
                    {eggFeatures.includes('java_version') && <JavaVersionModalFeature/>}
                </React.Suspense>
            </div>
        </ServerContentBlock>
    );
};

export default memo(ServerConsole, isEqual);
