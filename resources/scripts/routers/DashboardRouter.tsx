import * as React from 'react';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import AccountOverviewContainer from '@/components/dashboard/AccountOverviewContainer';
import NavigationBar from '@/components/NavigationBar';
import DashboardContainer from '@/components/dashboard/DashboardContainer';
import TransitionRouter from '@/TransitionRouter';
import AccountApiContainer from '@/components/dashboard/AccountApiContainer';
import NotFound from '@/components/screens/NotFound';

export default ({ location }: RouteComponentProps) => (
    <>
        <NavigationBar/>
        {location.pathname.startsWith('/account') &&
        <div id={'sub-navigation'}>
            <div className={'items'}>
                <NavLink to={'/account'} exact>Settings</NavLink>
                <NavLink to={'/account/api'}>API Credentials</NavLink>
            </div>
        </div>
        }
        <TransitionRouter>
            <Switch location={location}>
                <Route path={'/'} component={DashboardContainer} exact/>
                <Route path={'/account'} component={AccountOverviewContainer} exact/>
                <Route path={'/account/api'} component={AccountApiContainer} exact/>
                <Route path={'*'} component={NotFound}/>
            </Switch>
        </TransitionRouter>
    </>
);
