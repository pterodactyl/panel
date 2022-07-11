import tw from 'twin.macro';
import React, { useEffect } from 'react';
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
    }, [title]);

    return (
        <CSSTransition timeout={150} classNames={'fade'} appear in>
            <div css={tw`my-4`}>
                <ContentContainer className={className}>
                    {showFlashKey && <FlashMessageRender byKey={showFlashKey} css={tw`mb-4`} />}
                    {children}
                </ContentContainer>
                <ContentContainer css={tw`text-sm text-center my-4`}>
                    <p css={tw`text-neutral-500 sm:float-left`}>
                        &copy; <a href={'https://jexactyl.com'}>Jexactyl,</a> built on{' '}
                        <a href={'https://pterodactyl.io'}>Pterodactyl.</a>
                    </p>
                    <p css={tw`text-neutral-500 sm:float-right`}>
                        <a href={'https://jexactyl.com'}> Site </a>
                        &bull;
                        <a href={'https://github.com/jexactyl/jexactyl'}> GitHub </a>
                    </p>
                </ContentContainer>
            </div>
        </CSSTransition>
    );
};

export default PageContentBlock;
