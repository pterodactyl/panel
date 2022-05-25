import React from 'react';
import tw from 'twin.macro';
import * as Icon from 'react-feather';
import TransitionRouter from '@/TransitionRouter';
import NavigationBar from '@/components/NavigationBar';
import { NotFound } from '@/components/elements/ScreenBlock';
import SubNavigation from '@/components/elements/SubNavigation';
import BalanceContainer from '@/components/store/BalanceContainer';
import OverviewContainer from '@/components/store/OverviewContainer';
import ProductsContainer from '@/components/store/order/ProductsContainer';
import { NavLink, Route, RouteComponentProps, Switch } from 'react-router-dom';

const StoreRouter = ({ location, match }: RouteComponentProps) => {
    return (
        <>
            <NavigationBar />
            <SubNavigation>
                <div>
                    <NavLink to={`${match.url}`} exact>
                        <div css={tw`flex items-center justify-between`}>Overview <Icon.Home css={tw`ml-1`} size={18} /></div>
                    </NavLink>
                    <NavLink to={`${match.url}/balance`}>
                        <div css={tw`flex items-center justify-between`}>Balance <Icon.DollarSign css={tw`ml-1`} size={18} /></div>
                    </NavLink>
                    <NavLink to={`${match.url}/order`}>
                        <div css={tw`flex items-center justify-between`}>Order <Icon.ShoppingCart css={tw`ml-1`} size={18} /></div>
                    </NavLink>
                </div>
            </SubNavigation>
            <TransitionRouter>
                <Switch location={location}>
                    <Route path={`${match.path}`} exact>
                        <OverviewContainer />
                    </Route>
                    <Route path={`${match.path}/balance`} exact>
                        <BalanceContainer />
                    </Route>
                    <Route path={`${match.path}/order`} exact>
                        <ProductsContainer />
                    </Route>
                    <Route path={'*'}>
                        <NotFound />
                    </Route>
                </Switch>
            </TransitionRouter>
        </>
    );
};

export default StoreRouter;
