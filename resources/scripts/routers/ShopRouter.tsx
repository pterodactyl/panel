import React from 'react';
import { NavLink, Route, Switch } from 'react-router-dom';
import NavigationBar from '@/components/NavigationBar';
import { NotFound } from '@/components/elements/ScreenBlock';
import TransitionRouter from '@/TransitionRouter';
import SubNavigation from '@/components/elements/SubNavigation';
import PaymentContainer from '@/components/shop/PaymentContainer';
import CategoriesContainer from '@/components/shop/CategoriesContainer';
import GamesContainer from '@/components/shop/GamesContainer';
import PaymentCancelled from '@/components/shop/payment/PaymentCancelled';
import { useLocation } from 'react-router';
import Spinner from '@/components/elements/Spinner';

export default () => {
    const location = useLocation();

    return (
        <>
            <NavigationBar />
            {location.pathname.startsWith('/shop') &&
                <SubNavigation>
                    <div>
                        <NavLink to={'/shop'} exact>Shop</NavLink>
                        <NavLink to={'/shop/payments'} exact>Payments</NavLink>
                    </div>
                </SubNavigation>
            }
            <TransitionRouter>
                <React.Suspense fallback={<Spinner centered />}>
                    <Switch location={location}>
                        <Route path={'/shop'} exact>
                            <CategoriesContainer />
                        </Route>
                        <Route path={'/shop/payments'} exact>
                            <PaymentContainer />
                        </Route>
                        <Route path={'/shop/payments/:provider/success/:sessionId'} exact>
                            <PaymentContainer />
                        </Route>
                        <Route path={'/shop/payments/:provider/cancelled'} exact>
                            <PaymentCancelled />
                        </Route>
                        <Route path={'/shop/:category'} exact>
                            <GamesContainer />
                        </Route>
                        <Route path={'*'}>
                            <NotFound />
                        </Route>
                    </Switch>
                </React.Suspense>
            </TransitionRouter>
        </>
    );
};
