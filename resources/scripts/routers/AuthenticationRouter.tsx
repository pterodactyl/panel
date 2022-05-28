import React from 'react';
import { useHistory, useLocation } from 'react-router';
import { NotFound } from '@/components/elements/ScreenBlock';
import LoginContainer from '@/components/auth/LoginContainer';
import { Route, Switch, useRouteMatch } from 'react-router-dom';
import ResetPasswordContainer from '@/components/auth/ResetPasswordContainer';
import ForgotPasswordContainer from '@/components/auth/ForgotPasswordContainer';
import LoginCheckpointContainer from '@/components/auth/LoginCheckpointContainer';

export default () => {
    const history = useHistory();
    const location = useLocation();
    const { path } = useRouteMatch();

    return (
        <div className={'pt-8 xl:pt-32'}>
            <Switch location={location}>
                <Route path={`${path}/login`} component={LoginContainer} exact/>
                <Route path={`${path}/login/checkpoint`} component={LoginCheckpointContainer}/>
                <Route path={`${path}/password`} component={ForgotPasswordContainer} exact/>
                <Route path={`${path}/password/reset/:token`} component={ResetPasswordContainer}/>
                <Route path={`${path}/checkpoint`}/>
                <Route path={'*'}>
                    <NotFound onBack={() => history.push('/auth/login')}/>
                </Route>
            </Switch>
        </div>
    );
};
