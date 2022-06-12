import React from 'react';
import tw from 'twin.macro';
import { ServerContext } from '@/state/server';
import TitledGreyBox from '@/components/elements/TitledGreyBox';

const ServerConfigurationBlock = () => {
    const renewal = ServerContext.useStoreState(state => state.server.data?.renewal);
    const renewable = ServerContext.useStoreState(state => state.server.data?.renewable);

    return (
        <TitledGreyBox css={tw`break-words mt-4`} title={'Server Renewals'}>
            <p css={tw`text-sm my-1`}>
                {!renewable ?
                    <>This server is exempt from renewals.</>
                    :
                    <>{renewal} days until renewal</>
                }
            </p>
        </TitledGreyBox>
    );
};

export default ServerConfigurationBlock;
