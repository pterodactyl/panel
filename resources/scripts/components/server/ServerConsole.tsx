import tw from 'twin.macro';
import isEqual from 'react-fast-compare';
import React, { lazy, memo } from 'react';
import Can from '@/components/elements/Can';
import Fade from '@/components/elements/Fade';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import StatBars from '@/components/server/StatBars';
import PowerControls from '@/components/server/PowerControls';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import ServerConfigurationBlock from './ServerConfigurationBlock';
import ContentContainer from '@/components/elements/ContentContainer';
import ServerDetailsBlock from '@/components/server/ServerDetailsBlock';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import {
    EulaModalFeature,
    JavaVersionModalFeature,
    GSLTokenModalFeature,
    PIDLimitModalFeature,
    SteamDiskSpaceFeature,
} from '@feature/index';

export type PowerAction = 'start' | 'stop' | 'restart' | 'kill';

const ChunkedConsole = lazy(() => import(/* webpackChunkName: "console" */'@/components/server/Console'));
const ChunkedStatGraphs = lazy(() => import(/* webpackChunkName: "graphs" */'@/components/server/StatGraphs'));

const ServerConsole = () => {
    const status = ServerContext.useStoreState(state => state.status.value);
    const isInstalling = ServerContext.useStoreState(state => state.server.data!.isInstalling);
    const isTransferring = ServerContext.useStoreState(state => state.server.data!.isTransferring);
    const eggFeatures = ServerContext.useStoreState(state => state.server.data!.eggFeatures, isEqual);

    return (
        <ServerContentBlock title={'Console'}>
            <div css={tw`flex flex-wrap`}>
                <div css={tw` w-full lg:w-1/4`}>
                    <ServerDetailsBlock />
                    <ServerConfigurationBlock />
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
                </div>
                <div css={tw`w-full lg:w-3/4 mt-4 lg:mt-0 lg:pl-4`}>
                    <Can action={[ 'control.start', 'control.stop', 'control.restart' ]} matchAny>
                        <PowerControls/>
                    </Can>
                    <Spinner.Suspense>
                        <ErrorBoundary>
                            <ChunkedConsole/>
                        </ErrorBoundary>
                    </Spinner.Suspense>
                    <React.Suspense fallback={null}>
                        {eggFeatures.includes('eula') && <EulaModalFeature/>}
                        {eggFeatures.includes('java_version') && <JavaVersionModalFeature/>}
                        {eggFeatures.includes('gsl_token') && <GSLTokenModalFeature/>}
                        {eggFeatures.includes('pid_limit') && <PIDLimitModalFeature/>}
                        {eggFeatures.includes('steam_disk_space') && <SteamDiskSpaceFeature/>}
                    </React.Suspense>
                </div>
            </div>
            {status !== 'offline' &&
                <Fade timeout={500} in appear unmountOnExit>
                    <Spinner.Suspense>
                        <ErrorBoundary>
                            <ChunkedStatGraphs />
                        </ErrorBoundary>
                    </Spinner.Suspense>
                </Fade>
            }
        </ServerContentBlock>
    );
};

export default memo(ServerConsole, isEqual);
