import tw from 'twin.macro';
import React, { memo } from 'react';
import Features from '@feature/Features';
import isEqual from 'react-fast-compare';
import Can from '@/components/elements/Can';
import { useStoreState } from '@/state/hooks';
import Fade from '@/components/elements/Fade';
import { ServerContext } from '@/state/server';
import Console from '@/components/server/Console';
import Spinner from '@/components/elements/Spinner';
import StatBars from '@/components/server/StatBars';
import StatGraphs from '@/components/server/StatGraphs';
import PowerControls from '@/components/server/PowerControls';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import ContentContainer from '@/components/elements/ContentContainer';
import ServerDetailsBlock from '@/components/server/ServerDetailsBlock';
import ServerRenewalBlock from '@/components/server/ServerRenewalBlock';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import ConsoleShareContainer from '@/components/server/ConsoleShareContainer';
import ServerConfigurationBlock from '@/components/server/ServerConfigurationBlock';

export type PowerAction = 'start' | 'stop' | 'restart' | 'kill';

const ServerConsole = () => {
    const status = ServerContext.useStoreState(state => state.status.value);
    const renewal = useStoreState(state => state.settings.data?.renewal);
    const isInstalling = ServerContext.useStoreState(state => state.server.data!.isInstalling);
    const isTransferring = ServerContext.useStoreState(state => state.server.data!.isTransferring);
    const eggFeatures = ServerContext.useStoreState(state => state.server.data!.eggFeatures, isEqual);

    return (
        <ServerContentBlock title={'Console'}>
            <div css={tw`flex flex-wrap`}>
                <div css={tw` w-full lg:w-1/4`}>
                    <ServerDetailsBlock />
                    {renewal === 'true' ?
                        <ServerRenewalBlock />
                        :
                        <ServerConfigurationBlock />
                    }
                    <StatBars />
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
                        isTransferring &&
                            <div css={tw`mt-4 rounded bg-yellow-500 p-3`}>
                                <ContentContainer>
                                    <p css={tw`text-sm text-yellow-900`}>
                                        This server is currently being transferred to another node and all actions
                                        are unavailable.
                                    </p>
                                </ContentContainer>
                            </div>
                    }
                    <Can action={'console.share'}>
                        <ConsoleShareContainer />
                    </Can>
                </div>
                <div css={tw`w-full lg:w-3/4 mt-4 lg:mt-0 lg:pl-4`}>
                    <Can action={[ 'control.start', 'control.stop', 'control.restart' ]} matchAny>
                        <PowerControls/>
                    </Can>
                    <Spinner.Suspense>
                        <ErrorBoundary>
                            <Console />
                        </ErrorBoundary>
                    </Spinner.Suspense>
                    <Features enabled={eggFeatures} />
                </div>
            </div>
            {status !== 'offline' &&
                <Fade timeout={500} in appear unmountOnExit>
                    <Spinner.Suspense>
                        <ErrorBoundary>
                            <StatGraphs />
                        </ErrorBoundary>
                    </Spinner.Suspense>
                </Fade>
            }
        </ServerContentBlock>
    );
};

export default memo(ServerConsole, isEqual);
