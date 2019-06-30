import * as React from 'react';
import { hot } from 'react-hot-loader/root';
import { BrowserRouter, BrowserRouter as Router, Route, Switch } from 'react-router-dom';
import { StoreProvider } from 'easy-peasy';
import { store } from '@/state';
import DashboardRouter from '@/routers/DashboardRouter';
import ServerRouter from '@/routers/ServerRouter';
import AuthenticationRouter from '@/routers/AuthenticationRouter';
import { Provider } from 'react-redux';

interface WindowWithUser extends Window {
    PterodactylUser?: {
        uuid: string;
        username: string;
        email: string;
        root_admin: boolean;
        use_totp: boolean;
        language: string;
        updated_at: string;
        created_at: string;
    };
}

const App = () => {
    const data = (window as WindowWithUser).PterodactylUser;
    if (data && !store.getState().user.data) {
        store.getActions().user.setUserData({
            uuid: data.uuid,
            username: data.username,
            email: data.email,
            language: data.language,
            rootAdmin: data.root_admin,
            useTotp: data.use_totp,
            createdAt: new Date(data.created_at),
            updatedAt: new Date(data.updated_at),
        });
    }

    return (
        <StoreProvider store={store}>
            <Provider store={store}>
                <Router basename={'/'}>
                    <div className={'mx-auto w-auto'}>
                        <BrowserRouter basename={'/'}>
                            <Switch>
                                <Route path="/server/:id" component={ServerRouter}/>
                                <Route path="/auth" component={AuthenticationRouter}/>
                                <Route path="/" component={DashboardRouter}/>
                            </Switch>
                        </BrowserRouter>
                    </div>
                </Router>
            </Provider>
        </StoreProvider>
    );
};

export default hot(App);
