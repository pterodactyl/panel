import React, { useEffect } from 'react';
import ContentContainer from '@/components/elements/ContentContainer';
import { CSSTransition } from 'react-transition-group';
import tw from 'twin.macro';
import FlashMessageRender from '@/components/FlashMessageRender';

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
            <>
                <ContentContainer css={tw`my-4 sm:my-10`} className={className}>
                    {showFlashKey && <FlashMessageRender byKey={showFlashKey} css={tw`mb-4`} />}
                    {children}
                </ContentContainer>
                <ContentContainer css={tw`mb-4`}>
                    <p css={tw`text-center text-neutral-500 text-xs`}>
                        <a
                            rel={'noopener nofollow noreferrer'}
                            href={'https://www.frostbyt3gaming.com/'}
                            target={'_blank'}
                            css={tw`no-underline text-neutral-500 hover:text-neutral-300`}
                        >
                            Frostbyt3 Gaming
                        </a>
                        &nbsp;&copy; 2023 - {new Date().getFullYear()}
                    </p>
                    <p css={tw`text-center text-xs`}>
                        <a href='https://streamlabs.com/relykstv/tip' target='_blank' rel='noopener nofollow noreferrer'><button className='btn btn-success w-full'><i className='fa fa-fw fa-money'></i> Donate</button></a>
                    </p>
                </ContentContainer>
            </>
        </CSSTransition>
    );
};

export default PageContentBlock;
