import * as React from 'react';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';
import AccountOverviewContainer from '@/components/dashboard/AccountOverviewContainer';
import NavigationBar from '@/components/NavigationBar';
import DashboardContainer from '@/components/dashboard/DashboardContainer';
import AccountApiContainer from '@/components/dashboard/AccountApiContainer';
import NotFound from '@/components/screens/NotFound';
import styled from 'styled-components/macro';
import tw from 'twin.macro';
import config from '@/../../tailwind.config.js';
import TransitionRouter from '@/TransitionRouter';

const SubNavigation = styled.div`
    ${tw`w-full bg-neutral-700 shadow`};
    
    & > div {
        ${tw`flex items-center text-sm mx-auto px-2`};
        max-width: 1200px;
        
        & > a, & > div {
            ${tw`inline-block py-3 px-4 text-neutral-300 no-underline transition-all duration-150`};
            
            &:not(:first-of-type) {
                ${tw`ml-2`};
            }
            
            &:active, &:hover {
                ${tw`text-neutral-100`};
            }
            
            &:active, &:hover, &.active {
                box-shadow: inset 0 -2px ${config.theme.colors.cyan['500']};
            }
        }
    }
`;

export default ({ location }: RouteComponentProps) => (
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
                <Route path={'/'} component={DashboardContainer} exact/>
                <Route path={'/account'} component={AccountOverviewContainer} exact/>
                <Route path={'/account/api'} component={AccountApiContainer} exact/>
                <Route path={'*'} component={NotFound}/>
            </Switch>
        </TransitionRouter>
    </>
);
