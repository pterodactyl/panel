import React from 'react';
import { Route, RouteComponentProps } from 'react-router-dom';
import LoginContainer from '@/components/auth/LoginContainer';
import ForgotPasswordContainer from '@/components/auth/ForgotPasswordContainer';
import ResetPasswordContainer from '@/components/auth/ResetPasswordContainer';
import LoginCheckpointContainer from '@/components/auth/LoginCheckpointContainer';

export default ({ match }: RouteComponentProps) => (
    <div className={'mt-32'}>
        <Route path={`${match.path}/login`} component={LoginContainer} exact/>
        <Route path={`${match.path}/login/checkpoint`} component={LoginCheckpointContainer}/>
        <Route path={`${match.path}/password`} component={ForgotPasswordContainer} exact/>
        <Route path={`${match.path}/password/reset/:token`} component={ResetPasswordContainer}/>
        <Route path={`${match.path}/checkpoint`}/>
    </div>
);
