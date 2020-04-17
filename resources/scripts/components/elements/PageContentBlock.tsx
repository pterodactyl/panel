import React from 'react';
import ContentContainer from '@/components/elements/ContentContainer';
import { CSSTransition } from 'react-transition-group';

interface Props {
    children: React.ReactNode;
    className?: string;
}

export default ({ className, children }: Props) => (
    <CSSTransition timeout={250} classNames={'fade'} appear={true} in={true}>
        <>
            <ContentContainer className={`my-10 ${className}`}>
                {children}
            </ContentContainer>
            <ContentContainer className={'mb-4'}>
                <p className={'text-right text-neutral-500 text-xs'}>
                    &copy; 2015 - 2020&nbsp;
                    <a
                        rel={'noopener nofollow'}
                        href={'https://pterodactyl.io'}
                        target={'_blank'}
                        className={'no-underline text-neutral-500 hover:text-neutral-300'}
                    >
                        Pterodactyl Software
                    </a>
                </p>
            </ContentContainer>
        </>
    </CSSTransition>
);
