import React from 'react';
import { NotFound } from '@/components/elements/ScreenBlock';
import LoginContainer from '@/components/auth/LoginContainer';
import { Route, RouteComponentProps, Switch } from 'react-router-dom';
import ResetPasswordContainer from '@/components/auth/ResetPasswordContainer';
import ForgotPasswordContainer from '@/components/auth/ForgotPasswordContainer';
import LoginCheckpointContainer from '@/components/auth/LoginCheckpointContainer';

export default ({ location, history, match }: RouteComponentProps) => (
    <div className={'pt-8 xl:pt-32'}>
        <Switch location={location}>
            <Route path={`${match.path}/login`} component={LoginContainer} exact/>
            <Route path={`${match.path}/login/checkpoint`} component={LoginCheckpointContainer}/>
            <Route path={`${match.path}/password`} component={ForgotPasswordContainer} exact/>
            <Route path={`${match.path}/password/reset/:token`} component={ResetPasswordContainer}/>
            <Route path={`${match.path}/checkpoint`}/>
            <Route path={'*'}>
                <NotFound onBack={() => history.push('/auth/login')}/>
            </Route>
        </Switch>
    </div>
);
