import React from 'react';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import TransitionRouter from '@/TransitionRouter';
import SidePanel from '@/components/SidePanel';
import DashboardContainer from '@/components/dashboard/DashboardContainer';
import AccountOverviewContainer from '@/components/dashboard/AccountOverviewContainer';
import AccountApiContainer from '@/components/dashboard/AccountApiContainer';
import SecurityKeyContainer from '@/components/dashboard/SecurityKeyContainer';
import { NotFound } from '@/components/elements/ScreenBlock';
import SubNavigation from '@/components/elements/SubNavigation';

export default ({ location }: RouteComponentProps) => (
    <>
        <SidePanel/>
        {location.pathname.startsWith('/account') &&
        <SubNavigation>
            <div>
                <NavLink to={'/account'} exact>Settings</NavLink>
                <NavLink to={'/account/api'}>API Credentials</NavLink>
                <NavLink to={'/account/keys/security'}>Security Keys</NavLink>
            </div>
        </SubNavigation>
        }
        <TransitionRouter>
            <Switch location={location}>
                <Route path={'/'} exact>
                    <DashboardContainer/>
                </Route>
                <Route path={'/account'} exact>
                    <AccountOverviewContainer/>
                </Route>
                <Route path={'/account/api'} exact>
                    <AccountApiContainer/>
                </Route>
                <Route path={'/account/keys/security'} exact>
                    <SecurityKeyContainer/>
                </Route>
                <Route path={'*'}>
                    <NotFound/>
                </Route>
            </Switch>
        </TransitionRouter>
    </>
);
