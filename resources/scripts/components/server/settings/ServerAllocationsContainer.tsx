import React from 'react';
import TitledGreyBox from '@/components/elements/TitledGreyBox';
import { ServerContext } from '@/state/server';
import tw from 'twin.macro';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faNetworkWired } from '@fortawesome/free-solid-svg-icons';
import styled from 'styled-components/macro';

const Code = styled.code`${tw`font-mono py-1 px-2 bg-neutral-900 rounded text-sm block`}`;
const Label = styled.label`${tw`uppercase text-xs mt-1 text-neutral-400 block px-1 select-none transition-colors duration-150`}`;

const Row = styled.div`
    ${tw`flex items-center py-2 pl-4 pr-5 border-l-4 border-transparent transition-colors duration-150`};
    
    & svg {
        ${tw`transition-colors duration-150`};
    }
    
    &:hover {
        ${tw`border-cyan-400`};
        
        svg {
            ${tw`text-neutral-100`};
        }
        
        ${Label} {
            ${tw`text-neutral-200`};
        }
    }
`;

export default () => {
    const allocations = ServerContext.useStoreState(state => state.server.data!.allocations);

    return (
        <TitledGreyBox title={'Allocated Ports'}>
            {allocations.map(({ ip, port, alias, isDefault }, index) => (
                <Row key={`${ip}:${port}`} css={index > 0 ? tw`mt-2` : undefined}>
                    <div css={tw`mr-4 text-neutral-400`}>
                        <FontAwesomeIcon icon={faNetworkWired}/>
                    </div>
                    <div css={tw`mr-4`}>
                        <Code>{alias || ip}</Code>
                        <Label>IP Address</Label>
                    </div>
                    <div>
                        <Code>:{port}</Code>
                        <Label>Port</Label>
                    </div>
                    <div css={tw`flex-1 text-right`}>
                        {isDefault ?
                            <span css={tw`bg-green-500 py-1 px-2 rounded text-green-50 text-xs`}>
                                Default
                            </span>
                            :
                            null
                        }
                    </div>
                </Row>
            ))}
        </TitledGreyBox>
    );
};
