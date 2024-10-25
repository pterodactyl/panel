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
                <ContentContainer css={tw`mb-4 flex justify-between items-center`}>
                    <div css={tw`flex items-center text-[#606d7b] xs2:hidden`}>
                        <a
                            rel={'noopener nofollow noreferrer'}
                            href={'https://www.arion2000.xyz'}
                            target={'_blank'}
                            css={tw`transition-opacity duration-300 ease-in-out`}
                        >
                            <img
                                src={'/assets/img/footer_copyright_watermark.png'}
                                alt={'a2data logo'}
                                css={tw`h-6 opacity-40 hover:opacity-100 grayscale transition-all duration-300 ease-in-out hover:grayscale-0`}
                            />
                        </a>
                        <span css={tw`ml-2`}>&copy; {new Date().getFullYear()}</span>
                    </div>
                    <div css={tw`text-neutral-500 text-xs`}>
                        <a
                            rel={'noopener nofollow noreferrer'}
                            href={'https://status.arion2000.xyz/?ref=panel.arion2000.xyz&utm=62afe015-b024-41fc-9b55-41ec9a340ea6'}
                            target={'_blank'}
                            css={tw`no-underline text-neutral-500 hover:text-neutral-300`}
                        >
                            Status
                        </a>
                        <span css={tw`mx-2`}>-</span>
                        <a
                            rel={'noopener nofollow noreferrer'}
                            href={'https://mc.a2data.site/legal/imprint'}
                            target={'_blank'}
                            css={tw`no-underline text-neutral-500 hover:text-neutral-300`}
                        >
                            Impressum
                        </a>
                        <span css={tw`mx-2`}>-</span>
                        <a
                            rel={'noopener nofollow noreferrer'}
                            href={'https://mc.a2data.site/legal/privacy'}
                            target={'_blank'}
                            css={tw`no-underline text-neutral-500 hover:text-neutral-300`}
                        >
                            Datenschutz
                        </a>
                    </div>
                </ContentContainer>
            </>
        </CSSTransition>
    );
};

export default PageContentBlock;
