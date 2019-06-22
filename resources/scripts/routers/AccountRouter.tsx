import * as React from 'react';
import { BrowserRouter, Route, Switch } from 'react-router-dom';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import DesignElements from '@/components/account/DesignElements';

export default class AccountRouter extends React.PureComponent {
    render () {
        return (
            <BrowserRouter basename={'/account'}>
                <Route
                    render={({ location }) => (
                        <TransitionGroup className={'route-transition-group'}>
                            <CSSTransition key={location.key} timeout={150} classNames={'fade'}>
                                <section>
                                    <Switch location={location}>
                                        <Route path={'/'} component={DesignElements} exact/>
                                        <Route path={'/design'} component={DesignElements} exact/>
                                    </Switch>
                                    <p className={'text-right text-neutral-500 text-xs'}>
                                        &copy; 2015 - 2019&nbsp;
                                        <a
                                            href={'https://pterodactyl.io'}
                                            className={'no-underline text-neutral-500 hover:text-neutral-300'}
                                        >
                                            Pterodactyl Software
                                        </a>
                                    </p>
                                </section>
                            </CSSTransition>
                        </TransitionGroup>
                    )}
                />
            </BrowserRouter>
        );
    }
}
