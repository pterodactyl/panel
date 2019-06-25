import React from 'react';
import { Route, Switch } from 'react-router';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import { BrowserRouter } from 'react-router-dom';

type Props = Readonly<{
    basename: string;
    children: React.ReactNode;
}>;

export default ({ basename, children }: Props) => (
    <BrowserRouter basename={basename}>
        <Route
            render={({ location }) => (
                <TransitionGroup className={'route-transition-group'}>
                    <CSSTransition key={location.key} timeout={150} classNames={'fade'}>
                        <section>
                            <Switch location={location}>
                                {children}
                            </Switch>
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
    </BrowserRouter>
);
