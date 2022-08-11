import React, { lazy } from 'react';
import { useStoreState } from '@/state/hooks';
import { ServerContext } from '@/state/server';
import { history } from '@/components/history';
import Spinner from '@/components/elements/Spinner';
import { Router, Switch, Route } from 'react-router';
import { NotApproved, NotFound } from '@/components/elements/ScreenBlock';
import AuthenticatedRoute from '@/components/elements/AuthenticatedRoute';

const StoreRouter = lazy(() => import(/* webpackChunkName: "auth" */ '@/routers/StoreRouter'));
const ServerRouter = lazy(() => import(/* webpackChunkName: "server" */ '@/routers/ServerRouter'));
const DashboardRouter = lazy(() => import(/* webpackChunkName: "dashboard" */ '@/routers/DashboardRouter'));
const AuthenticationRouter = lazy(() => import(/* webpackChunkName: "auth" */ '@/routers/AuthenticationRouter'));

const IndexRouter = () => {
    const authenticated = useStoreState((state) => state.user?.data);
    const approved = useStoreState((state) => state.user.data?.approved);
    const enabled = useStoreState((state) => state.storefront.data!.enabled);
    const approvals = useStoreState((state) => state.settings.data!.approvals);

    if (approvals && !approved && authenticated) {
        return (
            <NotApproved
                title={'Awaiting Approval'}
                message={'Your account is currently pending approval from an administator.'}
            />
        );
    }

    return (
        <Router history={history}>
            <Switch>
                <Route path={'/auth'}>
                    <Spinner.Suspense>
                        <AuthenticationRouter />
                    </Spinner.Suspense>
                </Route>
                <AuthenticatedRoute path={'/server/:id'}>
                    <Spinner.Suspense>
                        <ServerContext.Provider>
                            <ServerRouter />
                        </ServerContext.Provider>
                    </Spinner.Suspense>
                </AuthenticatedRoute>
                {enabled && (
                    <AuthenticatedRoute path={'/store'}>
                        <Spinner.Suspense>
                            <StoreRouter />
                        </Spinner.Suspense>
                    </AuthenticatedRoute>
                )}
                <AuthenticatedRoute path={'/'}>
                    <Spinner.Suspense>
                        <DashboardRouter />
                    </Spinner.Suspense>
                </AuthenticatedRoute>
                <Route path={'*'} component={NotFound} />
            </Switch>
        </Router>
    );
};

export default IndexRouter;
