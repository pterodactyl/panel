import React from 'react';
import ContentContainer from '@/components/elements/ContentContainer';
import { CSSTransition } from 'react-transition-group';
import tw from 'twin.macro';
import FlashMessageRender from '@/components/FlashMessageRender';

const PageContentBlock: React.FC<{ showFlashKey?: string; className?: string }> = ({ children, showFlashKey, className }) => (
    <CSSTransition timeout={150} classNames={'fade'} appear in>
        <>
            <ContentContainer css={tw`my-10`} className={className}>
                {showFlashKey &&
                <FlashMessageRender byKey={showFlashKey} css={tw`mb-4`}/>
                }
                {children}
            </ContentContainer>
            <ContentContainer css={tw`mb-4`}>
                <p css={tw`text-center text-neutral-500 text-xs`}>
                    &copy; 2015 - 2020&nbsp;
                    <a
                        rel={'noopener nofollow noreferrer'}
                        href={'https://pterodactyl.io'}
                        target={'_blank'}
                        css={tw`no-underline text-neutral-500 hover:text-neutral-300`}
                    >
                        Pterodactyl Software
                    </a>
                </p>
            </ContentContainer>
        </>
    </CSSTransition>
);

export default PageContentBlock;
