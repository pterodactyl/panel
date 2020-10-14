import React, { useEffect } from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import tw from 'twin.macro';
import VariableBox from '@/components/server/startup/VariableBox';
import ServerContentBlock from '@/components/elements/ServerContentBlock';
import getServerStartup from '@/api/swr/getServerStartup';
import Spinner from '@/components/elements/Spinner';
import ServerError from '@/components/screens/ServerError';
import { httpErrorToHuman } from '@/api/http';
import { ServerContext } from '@/state/server';
import { useDeepCompareEffect } from '@/plugins/useDeepCompareEffect';

const StartupContainer = () => {
    const uuid = ServerContext.useStoreState(state => state.server.data!.uuid);
    const invocation = ServerContext.useStoreState(state => state.server.data!.invocation);
    const variables = ServerContext.useStoreState(state => state.server.data!.variables);

    const { data, error, isValidating, mutate } = getServerStartup(uuid, { invocation, variables });

    const setServerFromState = ServerContext.useStoreActions(actions => actions.server.setServerFromState);

    useEffect(() => {
        // Since we're passing in initial data this will not trigger on mount automatically. We
        // want to always fetch fresh information from the API however when we're loading the startup
        // information.
        mutate();
    }, []);

    useDeepCompareEffect(() => {
        if (!data) return;

        setServerFromState(s => ({
            ...s,
            invocation: data.invocation,
            variables: data.variables,
        }));
    }, [ data ]);

    return (
        !data ?
            (!error || (error && isValidating)) ?
                <Spinner centered size={Spinner.Size.LARGE}/>
                :
                <ServerError
                    title={'Oops!'}
                    message={httpErrorToHuman(error)}
                    onRetry={() => mutate()}
                />
            :
            <ServerContentBlock title={'Startup Settings'}>
                <TitledGreyBox title={'Startup Command'}>
                    <div css={tw`px-1 py-2`}>
                        <p css={tw`font-mono bg-neutral-900 rounded py-2 px-4`}>
                            {data.invocation}
                        </p>
                    </div>
                </TitledGreyBox>
                <div css={tw`grid gap-8 md:grid-cols-2 mt-10`}>
                    {data.variables.map(variable => <VariableBox key={variable.envVariable} variable={variable}/>)}
                </div>
            </ServerContentBlock>
    );
};

export default StartupContainer;
