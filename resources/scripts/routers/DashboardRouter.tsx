import React from 'react';
import { Route, RouteComponentProps, Switch } from 'react-router-dom';
import AccountOverviewContainer from '@/components/dashboard/AccountOverviewContainer';
import DashboardContainer from '@/components/dashboard/DashboardContainer';
import AccountApiContainer from '@/components/dashboard/AccountApiContainer';
import SecurityKeyContainer from '@/components/dashboard/SecurityKeyContainer';
import { NotFound } from '@/components/elements/ScreenBlock';
import TransitionRouter from '@/TransitionRouter';
import SidePanel from '@/components/SidePanel';
import tw from 'twin.macro';

export default ({ location }: RouteComponentProps) => (
    <div css={tw`flex flex-row`}>
        <SidePanel />
        <div css={tw`flex-grow flex-shrink pl-40`}>
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
        </div>
    </div>
);
