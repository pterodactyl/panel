import React from 'react';
import tw from 'twin.macro';
import { breakpoint } from '@/theme';
import styled from 'styled-components/macro';
import { useStoreState } from '@/state/hooks';
import FlashMessageRender from '@/components/FlashMessageRender';

const Wrapper = styled.div`
    ${breakpoint('sm')`
        ${tw`w-4/5 mx-auto`}
    `};
    ${breakpoint('md')`
        ${tw`p-10`}
    `};
    ${breakpoint('lg')`
        ${tw`w-3/5`}
    `};
    ${breakpoint('xl')`
        ${tw`w-full`}
        max-width: 700px;
    `};
`;

const DiscordFormContainer = ({ children }: { children: React.ReactNode }) => {
    const name = useStoreState((state) => state.settings.data!.name);

    return (
        <div>
            <Wrapper>
                <h2 css={tw`text-3xl text-center text-neutral-100 font-medium py-4`}>Login to {name}</h2>
                <FlashMessageRender css={tw`mb-2 px-1`} />
                <div css={tw`md:flex w-full bg-neutral-900 shadow-lg rounded-lg p-6 md:pl-0 mx-1`}>
                    <div css={tw`flex-none select-none mb-6 md:mb-0 self-center`}>
                        <img src={'/assets/svgs/discord.svg'} css={tw`block w-48 p-8 md:w-64 mx-auto`} />
                    </div>
                    <div css={tw`flex-1`}>{children}</div>
                </div>
                <p css={tw`text-neutral-500 text-xs mt-6 sm:float-left`}>
                    &copy; <a href={'https://jexactyl.com'}>Jexactyl,</a> built on{' '}
                    <a href={'https://pterodactyl.io'}>Pterodactyl.</a>
                </p>
                <p css={tw`text-neutral-500 text-xs mt-6 sm:float-right`}>
                    <a href={'https://jexactyl.com'}> Site </a>
                    &bull;
                    <a href={'https://github.com/jexactyl/jexactyl'}> GitHub </a>
                </p>
            </Wrapper>
        </div>
    );
};

export default DiscordFormContainer;
