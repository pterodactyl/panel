import * as React from 'react';
import { BrowserRouter, Route, Switch } from 'react-router-dom';
import LoginContainer from '@/components/auth/LoginContainer';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import ForgotPasswordContainer from '@/components/auth/ForgotPasswordContainer';
import FlashMessageRender from '@/components/FlashMessageRender';

export default class AuthenticationRouter extends React.PureComponent {
    render () {
        return (
            <BrowserRouter basename={'/auth'}>
                <Route
                    render={({ location }) => (
                        <TransitionGroup className={'route-transition-group mt-32'}>
                            <CSSTransition key={location.key} timeout={150} classNames={'fade'}>
                                <section>
                                    <div className={'mb-2'}>
                                        <FlashMessageRender/>
                                    </div>
                                    <Switch location={location}>
                                        <Route path={'/login'} component={LoginContainer}/>
                                        <Route path={'/forgot-password'} component={ForgotPasswordContainer}/>
                                        <Route path={'/checkpoint'}/>
                                    </Switch>
                                    <p className={'text-center text-neutral-500 text-xs'}>
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
