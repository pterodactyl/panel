import React from 'react';
import { Route } from 'react-router';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import styled from 'styled-components';
import { breakpoint } from 'styled-components-breakpoint';

type Props = Readonly<{
    children: React.ReactNode;
}>;

const ContentContainer = styled.div`
    max-width: 1200px;
    ${tw`mx-4`};

    ${breakpoint('xl')`
        ${tw`mx-auto`};
    `};
`;

export default ({ children }: Props) => (
    <Route
        render={({ location }) => (
            <TransitionGroup className={'route-transition-group'}>
                <CSSTransition key={location.key} timeout={250} in={true} appear={true} classNames={'fade'}>
                    <section>
                        <ContentContainer>
                            {children}
                        </ContentContainer>
                        <ContentContainer className={'mb-4'}>
                            <p className={'text-right text-neutral-500 text-xs'}>
                                &copy; 2015 - 2019&nbsp;
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
                    </section>
                </CSSTransition>
            </TransitionGroup>
        )}
    />
);
