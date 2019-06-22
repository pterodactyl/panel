import * as React from 'react';
import { hot } from 'react-hot-loader/root';
import { BrowserRouter as Router, Route } from 'react-router-dom';
import AuthenticationRouter from '@/routers/AuthenticationRouter';
import AccountRouter from '@/routers/AccountRouter';
import ServerOverviewContainer from '@/components/ServerOverviewContainer';
import { StoreProvider } from 'easy-peasy';
import { store } from '@/state';

class App extends React.PureComponent {
    componentDidMount () {

    }

    render () {
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
    }
}

export default hot(App);
