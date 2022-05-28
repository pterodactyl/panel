import tw from 'twin.macro';
import React, { useEffect } from 'react';
import GitInfo from 'react-git-info/macro';
import { CSSTransition } from 'react-transition-group';
import FlashMessageRender from '@/components/FlashMessageRender';
import ContentContainer from '@/components/elements/ContentContainer';

export interface PageContentBlockProps {
    title?: string;
    className?: string;
    showFlashKey?: string;
}

const PageContentBlock: React.FC<PageContentBlockProps> = ({ title, showFlashKey, className, children }) => {
    useEffect(() => {
        if (title) {
            document.title = title;
        }
    }, [ title ]);

    return (
        <CSSTransition timeout={150} classNames={'fade'} appear in>
            <>
                <ContentContainer css={tw`my-4 sm:my-10`} className={className}>
                    {showFlashKey &&
                    <FlashMessageRender byKey={showFlashKey} css={tw`mb-4`}/>
                    }
                    {children}
                </ContentContainer>
                <ContentContainer css={tw`mb-4 text-xs text-center`}>
                    <p css={tw`text-neutral-500 sm:float-left`}>
                            &copy; <a href={'https://jexactyl.com'}>Jexactyl,</a> built on <a href={'https://pterodactyl.io'}>Pterodactyl.</a>
                    </p>
                    <p css={tw`text-neutral-500 mt-2 sm:mt-0 sm:float-right`}>
                        <a href={'https://github.com/jexactyl/jexactyl'}>{GitInfo().commit.shortHash}</a>
                    </p>
                </ContentContainer>
            </>
        </CSSTransition>
    );
};

export default PageContentBlock;
