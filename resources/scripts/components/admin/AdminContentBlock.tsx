import React from 'react';
import { CSSTransition } from 'react-transition-group';
import tw from 'twin.macro';
import FlashMessageRender from '@/components/FlashMessageRender';

const PageContentBlock: React.FC<{ showFlashKey?: string; className?: string }> = ({ children, showFlashKey }) => (
    <CSSTransition timeout={150} classNames={'fade'} appear in>
        <>
            {showFlashKey &&
            <FlashMessageRender byKey={showFlashKey} css={tw`mb-4`}/>
            }
            {children}

            {/* <p css={tw`text-center text-neutral-500 text-xs mt-4`}>
                &copy; 2015 - 2020&nbsp;
                <a
                    rel={'noopener nofollow noreferrer'}
                    href={'https://pterodactyl.io'}
                    target={'_blank'}
                    css={tw`no-underline text-neutral-500 hover:text-neutral-300`}
                >
                    Pterodactyl Software
                </a>
            </p> */}
        </>
    </CSSTransition>
);

export default PageContentBlock;
