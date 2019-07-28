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
                        <div className={'mx-auto w-full'} style={{ maxWidth: '1200px' }}>
                            <p className={'text-right text-neutral-500 text-xs'}>
                                &copy; 2015 - 2019&nbsp;
                                <a
                                    href={'https://pterodactyl.io'}
                                    className={'no-underline text-neutral-500 hover:text-neutral-300'}
                                >
                                    Pterodactyl Software
                                </a>
                            </p>
                        </div>
                    </section>
                </CSSTransition>
            </TransitionGroup>
        )}
    />
);
