import React from 'react';
import tw from 'twin.macro';
import * as Icon from 'react-feather';
import { useLocation } from 'react-router';
import TransitionRouter from '@/TransitionRouter';
import NavigationBar from '@/components/NavigationBar';
import { NotFound } from '@/components/elements/ScreenBlock';
import SubNavigation from '@/components/elements/SubNavigation';
import EditContainer from '@/components/store/edit/EditContainer';
import BalanceContainer from '@/components/store/BalanceContainer';
import OverviewContainer from '@/components/store/OverviewContainer';
import { NavLink, Route, Switch, useRouteMatch } from 'react-router-dom';
import ProductsContainer from '@/components/store/order/ProductsContainer';

const StoreRouter = () => {
    const match = useRouteMatch<{ id: string }>();
    const location = useLocation();

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
                    <NavLink to={`${match.url}/edit`}>
                        <div css={tw`flex items-center justify-between`}>Edit <Icon.Edit css={tw`ml-1`} size={18} /></div>
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
                    <Route path={`${match.path}/edit`} exact>
                        <EditContainer />
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
