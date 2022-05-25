import React from 'react';
import { history } from '@/components/history';
import StoreRouter from '@/routers/StoreRouter';
import ServerRouter from '@/routers/ServerRouter';
import { Router, Switch, Route } from 'react-router';
import DashboardRouter from '@/routers/DashboardRouter';
import { NotFound } from '@/components/elements/ScreenBlock';
import AuthenticationRouter from '@/routers/AuthenticationRouter';

const IndexRouter = () => {
    return (
        <Router history={history}>
            <Switch>
                <Route path="/server/:id" component={ServerRouter} />
                <Route path="/auth" component={AuthenticationRouter} />
                <Route path="/store" component={StoreRouter} />
                <Route path="/" component={DashboardRouter} />
                <Route path={'*'} component={NotFound} />
            </Switch>
        </Router>
    );
};

export default IndexRouter;
