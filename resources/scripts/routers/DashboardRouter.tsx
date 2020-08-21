import React, { useEffect } from 'react';
import ReactGA from 'react-ga';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import AccountOverviewContainer from '@/components/dashboard/AccountOverviewContainer';
import NavigationBar from '@/components/NavigationBar';
import DashboardContainer from '@/components/dashboard/DashboardContainer';
import AccountApiContainer from '@/components/dashboard/AccountApiContainer';
import NotFound from '@/components/screens/NotFound';
import TransitionRouter from '@/TransitionRouter';
import SubNavigation from '@/components/elements/SubNavigation';

export default ({ location }: RouteComponentProps) => {
    useEffect(() => {
        ReactGA.pageview(location.pathname);
    }, [ location.pathname ]);

    return (
        <>
            <NavigationBar/>
            {location.pathname.startsWith('/account') &&
                <SubNavigation>
                    <div>
                        <NavLink to={'/account'} exact>Settings</NavLink>
                        <NavLink to={'/account/api'}>API Credentials</NavLink>
                    </div>
                </SubNavigation>
            }
            <TransitionRouter>
                <Switch location={location}>
                    <Route path={'/'} component={DashboardContainer} exact />
                    <Route path={'/account'} component={AccountOverviewContainer} exact/>
                    <Route path={'/account/api'} component={AccountApiContainer} exact/>
                    <Route path={'*'} component={NotFound} />
                </Switch>
            </TransitionRouter>
        </>
    );
};
