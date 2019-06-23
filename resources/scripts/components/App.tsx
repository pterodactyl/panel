import * as React from 'react';
import { hot } from 'react-hot-loader/root';
import { BrowserRouter as Router, Route } from 'react-router-dom';
import AuthenticationRouter from '@/routers/AuthenticationRouter';
import AccountRouter from '@/routers/AccountRouter';
import ServerOverviewContainer from '@/components/ServerOverviewContainer';
import { StoreProvider } from 'easy-peasy';
import { store } from '@/state';
import { UserData } from '@/state/types';

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
    if (data) {
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
            <Router basename={'/'}>
                <div className={'mx-auto px-10 w-auto'} style={{ maxWidth: '1000px' }}>
                    <Route exact path="/" component={ServerOverviewContainer}/>
                    <Route path="/auth" component={AuthenticationRouter}/>
                    <Route path="/account" component={AccountRouter}/>
                </div>
            </Router>
        </StoreProvider>
    );
};

export default hot(App);
