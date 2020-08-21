import React from 'react';
import { Route, RouteComponentProps, Switch } from 'react-router-dom';
import TransitionRouter from '@/TransitionRouter';
import NotFound from '@/components/screens/NotFound';
import AdminContainer from '@/components/admin/AdminContainer';

export default ({ location, match }: RouteComponentProps) => {
    return (
        <TransitionRouter>
            <Switch location={location}>
                <Route path={`${match.path}/`} component={AdminContainer} exact />
                <Route path={'*'} component={NotFound} />
            </Switch>
        </TransitionRouter>
    );
};
