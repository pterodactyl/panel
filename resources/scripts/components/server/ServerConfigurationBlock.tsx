import React from 'react';
import tw from 'twin.macro';
import * as Icon from 'react-feather';
import { ServerContext } from '@/state/server';
import TitledGreyBox from '@/components/elements/TitledGreyBox';

const ServerConfigurationBlock = () => {
    const id = ServerContext.useStoreState(state => state.server.data!.id);
    const node = ServerContext.useStoreState(state => state.server.data!.node);
    const image = ServerContext.useStoreState(state => state.server.data!.dockerImage);

    return (
        <TitledGreyBox css={tw`break-words mt-4`} title={'Server Information'}>
            <p css={tw`text-xs mt-2`}>
                <div css={tw`flex flex-row`}>
                    <Icon.List css={tw`mr-1`} size={16} />
                    {id}
                </div>
            </p>
            <p css={tw`text-xs mt-2`}>
                <div css={tw`flex flex-row truncate`}>
                    <Icon.Layers css={tw`mr-1`} size={16} />
                    {node}
                </div>
            </p>
            <p css={tw`text-xs mt-2`}>
                <div css={tw`flex flex-row truncate`}>
                    <Icon.Disc css={tw`mr-1`} size={16} />
                    {image}
                </div>
            </p>
        </TitledGreyBox>
    );
};

export default ServerConfigurationBlock;
