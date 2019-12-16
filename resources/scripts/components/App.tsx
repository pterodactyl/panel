import * as React from 'react';
import { hot } from 'react-hot-loader/root';
import { BrowserRouter, BrowserRouter as Router, Route, Switch } from 'react-router-dom';
import { StoreProvider } from 'easy-peasy';
import { store } from '@/state';
import DashboardRouter from '@/routers/DashboardRouter';
import ServerRouter from '@/routers/ServerRouter';
import AuthenticationRouter from '@/routers/AuthenticationRouter';
import { Provider } from 'react-redux';
import { SiteSettings } from '@/state/settings';

interface ExtendedWindow extends Window {
    SiteConfiguration?: SiteSettings;
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
    const { PterodactylUser, SiteConfiguration } = (window as ExtendedWindow);
    if (PterodactylUser && !store.getState().user.data) {
        store.getActions().user.setUserData({
            uuid: PterodactylUser.uuid,
            username: PterodactylUser.username,
            email: PterodactylUser.email,
            language: PterodactylUser.language,
            rootAdmin: PterodactylUser.root_admin,
            useTotp: PterodactylUser.use_totp,
            createdAt: new Date(PterodactylUser.created_at),
            updatedAt: new Date(PterodactylUser.updated_at),
        });
    }

    if (!store.getState().settings.data) {
        store.getActions().settings.setSettings(SiteConfiguration!);
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
