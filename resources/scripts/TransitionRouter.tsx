import React from 'react';
import { Route } from 'react-router';
import { CSSTransition, TransitionGroup } from 'react-transition-group';

type Props = Readonly<{
    children: React.ReactNode;
}>;

export default ({ children }: Props) => (
    <Route
        render={({ location }) => (
            <TransitionGroup className={'route-transition-group'}>
                <CSSTransition key={location.key} timeout={250} in={true} appear={true} classNames={'fade'}>
                    <section>
                        {children}
                    </section>
                </CSSTransition>
            </TransitionGroup>
        )}
    />
);
