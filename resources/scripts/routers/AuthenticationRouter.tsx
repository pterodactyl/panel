import React from 'react';
import { Route } from 'react-router-dom';
import LoginContainer from '@/components/auth/LoginContainer';
import ForgotPasswordContainer from '@/components/auth/ForgotPasswordContainer';
import ResetPasswordContainer from '@/components/auth/ResetPasswordContainer';
import TransitionRouter from '@/TransitionRouter';
import LoginCheckpointContainer from '@/components/auth/LoginCheckpointContainer';

export default () => (
    <TransitionRouter basename={'/auth'}>
        <div className={'mt-32'}>
            <Route path={'/login'} component={LoginContainer} exact/>
            <Route path={'/login/checkpoint'} component={LoginCheckpointContainer}/>
            <Route path={'/password'} component={ForgotPasswordContainer} exact/>
            <Route path={'/password/reset/:token'} component={ResetPasswordContainer}/>
            <Route path={'/checkpoint'}/>
        </div>
    </TransitionRouter>
);
