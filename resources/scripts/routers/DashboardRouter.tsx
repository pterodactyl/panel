import * as React from 'react';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import DesignElementsContainer from '@/components/dashboard/DesignElementsContainer';
import AccountOverviewContainer from '@/components/dashboard/AccountOverviewContainer';
import NavigationBar from '@/components/NavigationBar';
import DashboardContainer from '@/components/dashboard/DashboardContainer';
import TransitionRouter from '@/TransitionRouter';
import AccountApiContainer from '@/components/dashboard/AccountApiContainer';

export default ({ location }: RouteComponentProps) => (
    <React.Fragment>
        <NavigationBar/>
        {location.pathname.startsWith('/account') &&
        <div id={'sub-navigation'}>
            <div className={'items'}>
                <NavLink to={`/account`} exact>Settings</NavLink>
                <NavLink to={`/account/api`}>API Credentials</NavLink>
            </div>
        </div>
        }
        <TransitionRouter>
            <Switch location={location}>
                <Route path={'/'} component={DashboardContainer} exact/>
                <Route path={'/account'} component={AccountOverviewContainer} exact/>
                <Route path={'/account/api'} component={AccountApiContainer} exact/>
                <Route path={'/design'} component={DesignElementsContainer}/>
            </Switch>
        </TransitionRouter>
    </React.Fragment>
);
