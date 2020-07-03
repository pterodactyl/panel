import React from 'react';
import { Route } from 'react-router';
import { CSSTransition, TransitionGroup } from 'react-transition-group';

const TransitionRouter: React.FC = ({ children }) => (
    <Route
        render={({ location }) => (
            <TransitionGroup className={'route-transition-group'}>
                <CSSTransition key={location.key} timeout={250} in appear classNames={'fade'}>
                    <section>
                        {children}
                    </section>
                </CSSTransition>
            </TransitionGroup>
        )}
    />
);

export default TransitionRouter;
