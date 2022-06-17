import React, { memo } from 'react';
import isEqual from 'react-fast-compare';
import Spinner from '@/components/elements/Spinner';
import Features from '@feature/Features';
import Console from '@/components/server/Console';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import tw from 'twin.macro';
import ErrorBoundary from '@/components/elements/ErrorBoundary';
import { ServerContext } from '@/state/server';

const PopoutConsole = () => {
    const eggFeatures = ServerContext.useStoreState(state => state.server.data!.eggFeatures, isEqual);

    return (
        <ServerContentBlock title={'Console Popout'}>
            <div css={tw`absolute h-full w-full max-w-full left-0 top-0 p-0 m-0`}>
                <Spinner.Suspense>
                    <ErrorBoundary>
                        <Console css={tw`flex flex-wrap h-full`}/>
                    </ErrorBoundary>
                </Spinner.Suspense>
                <Features enabled={eggFeatures} />
            </div>
        </ServerContentBlock>
    );
};

export default memo(PopoutConsole, isEqual);
