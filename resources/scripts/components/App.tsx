import * as React from 'react';
import { hot } from 'react-hot-loader/root';
import { BrowserRouter as Router, Route } from 'react-router-dom';
import AuthenticationRouter from '@/routers/AuthenticationRouter';
import { Provider } from 'react-redux';
import { persistor, store } from '@/redux/configure';
import { PersistGate } from 'redux-persist/integration/react';
import AccountRouter from '@/routers/AccountRouter';
import ServerOverviewContainer from '@/components/ServerOverviewContainer';

class App extends React.PureComponent {
    render () {
        return (
            <Provider store={store}>
                <PersistGate persistor={persistor} loading={this.renderLoading()}>
                    <Router basename={'/'}>
                        <div className={'mx-auto px-10 w-auto'} style={{ maxWidth: '1000px' }}>
                            <Route exact path="/" component={ServerOverviewContainer}/>
                            <Route path="/auth" component={AuthenticationRouter}/>
                            <Route path="/account" component={AccountRouter}/>
                        </div>
                    </Router>
                </PersistGate>
            </Provider>
        );
    }

    renderLoading () {
        return (
            <div className={'spinner spinner-lg'}></div>
        );
    }
}

export default hot(App);
