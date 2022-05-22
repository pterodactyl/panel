import React from 'react';
import tw from 'twin.macro';
import * as Icon from 'react-feather';
import TransitionRouter from '@/TransitionRouter';
import NavigationBar from '@/components/NavigationBar';
import { NotFound } from '@/components/elements/ScreenBlock';
import SubNavigation from '@/components/elements/SubNavigation';
import DashboardContainer from '@/components/dashboard/DashboardContainer';
import AccountApiContainer from '@/components/dashboard/AccountApiContainer';
import StoreOverviewContainer from '@/components/store/StoreOverviewContainer';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import AccountSSHContainer from '@/components/dashboard/ssh/AccountSSHContainer';
import AccountOverviewContainer from '@/components/dashboard/AccountOverviewContainer';

export default ({ location }: RouteComponentProps) => (
    <>
        <NavigationBar/>
        {location.pathname.startsWith('/account') &&
        <SubNavigation>
            <div>
                <NavLink to={'/account'} exact>
                    <div css={tw`flex items-center justify-between`}>Account <Icon.User css={tw`ml-1`} size={18} /></div>
                </NavLink>
                <NavLink to={'/account/api'}>
                    <div css={tw`flex items-center justify-between`}>API <Icon.Code css={tw`ml-1`} size={18} /></div>
                </NavLink>
                <NavLink to={'/account/ssh'}>
                    <div css={tw`flex items-center justify-between`}>SSH Keys <Icon.Terminal css={tw`ml-1`} size={18} /></div>
                </NavLink>
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
                <Route path={'/account/ssh'} exact>
                    <AccountSSHContainer/>
                </Route>
                <Route path={'/store'} exact>
                    <StoreOverviewContainer />
                </Route>
                <Route path={'*'}>
                    <NotFound/>
                </Route>
            </Switch>
        </TransitionRouter>
    </>
);
