import * as React from 'react';
import { BrowserRouter, Route, Switch } from 'react-router-dom';
import LoginContainer from '@/components/auth/LoginContainer';
import { CSSTransition, TransitionGroup } from 'react-transition-group';
import ForgotPasswordContainer from '@/components/auth/ForgotPasswordContainer';
import FlashMessageRender from '@/components/FlashMessageRender';
import ResetPasswordContainer from '@/components/auth/ResetPasswordContainer';
import LoginCheckpointContainer from '@/components/auth/LoginCheckpointContainer';

export default class AuthenticationRouter extends React.PureComponent {
    render () {
        return (
            <BrowserRouter basename={'/auth'}>
                <Route
                    render={({ location }) => (
                        <TransitionGroup className={'route-transition-group mt-32'}>
                            <CSSTransition key={location.key} timeout={150} classNames={'fade'}>
                                <section>
                                    <FlashMessageRender/>
                                    <Switch location={location}>
                                        <Route path={'/login'} component={LoginContainer} exact/>
                                        <Route path={'/login/checkpoint'} component={LoginCheckpointContainer}/>
                                        <Route path={'/password'} component={ForgotPasswordContainer} exact/>
                                        <Route path={'/password/reset/:token'} component={ResetPasswordContainer}/>
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
