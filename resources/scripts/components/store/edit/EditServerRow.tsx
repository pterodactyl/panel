import React from 'react';
import tw from 'twin.macro';
import * as Icon from 'react-feather';
import { Link } from 'react-router-dom';
import styled from 'styled-components/macro';
import { Server } from '@/api/server/getServer';
import GreyRowBox from '@/components/elements/GreyRowBox';

const IconDescription = styled.p<{ $alarm?: boolean }>`
    ${tw`text-sm ml-2`};
    ${props => props.$alarm ? tw`text-white` : tw`text-neutral-400`};
`;

const StatusIndicatorBox = styled(GreyRowBox)`
    ${tw`grid grid-cols-12 gap-4 relative`};
`;

export default ({ server, className }: { server: Server; className?: string }) => {
    return (
        <StatusIndicatorBox as={Link} to={`/server/${server.id}/edit`} className={className}>
            <div css={tw`flex items-center col-span-12 sm:col-span-5 lg:col-span-6`}>
                <div className={'icon'} css={tw`mr-4`}>
                    <Icon.Server />
                </div>
                <div>
                    <p css={tw`text-lg break-words`}>{server.name}</p>
                    {!!server.description &&
                    <p css={tw`text-sm text-neutral-300 break-words`}>{server.description}</p>
                    }
                </div>
            </div>
            <div css={tw`hidden col-span-7 lg:col-span-4 sm:flex items-baseline justify-center`}>
                <React.Fragment>
                    <div css={tw`flex-1 ml-4 sm:block hidden`}>
                        <div css={tw`flex justify-center`}>
                            <Icon.ArrowRightCircle size={20} css={tw`text-neutral-600`} />
                            <IconDescription>
                                Edit Server
                            </IconDescription>
                        </div>
                    </div>
                </React.Fragment>
            </div>
        </StatusIndicatorBox>
    );
};
