import tw from 'twin.macro';
import React, { memo } from 'react';
import isEqual from 'react-fast-compare';
import Features from '@feature/Features';
import { ServerContext } from '@/state/server';
import Spinner from '@/components/elements/Spinner';
import Console from '@/components/server/console/Console';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import ServerContentBlock from '@/components/elements/ServerContentBlock';

const ExternalConsole = () => {
    const eggFeatures = ServerContext.useStoreState((state) => state.server.data!.eggFeatures, isEqual);

    return (
        <ServerContentBlock title={'Console'}>
            <div css={tw`absolute h-full w-full max-w-full left-0 top-0 p-0 m-0`}>
                <Spinner.Suspense>
                    <ErrorBoundary>
                        <Console css={tw`flex flex-wrap h-full`} />
                    </ErrorBoundary>
                </Spinner.Suspense>
                <Features enabled={eggFeatures} />
            </div>
        </ServerContentBlock>
    );
};

export default memo(ExternalConsole, isEqual);
