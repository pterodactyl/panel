import * as React from 'react';
import { BrowserRouter, Route, Switch } from 'react-router-dom';
import LoginContainer from '@/components/auth/LoginContainer';
import { CSSTransition, TransitionGroup } from 'react-transition-group';

export default class AuthenticationRouter extends React.PureComponent {
    render () {
        return (
            <BrowserRouter basename={'/auth'}>
                <Route
                    render={({ location }) => (
                        <TransitionGroup>
                            <CSSTransition key={location.key} timeout={150} classNames={'fade'}>
                                <Switch location={location}>
                                    <Route path={'/login'} component={LoginContainer}/>
                                    <Route path={'/forgot-password'}/>
                                    <Route path={'/checkpoint'}/>
                                </Switch>
                            </CSSTransition>
                        </TransitionGroup>
                    )}
                />
            </BrowserRouter>
        );
    }
}
