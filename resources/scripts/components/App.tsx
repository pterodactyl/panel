import * as React from 'react';
import { hot } from 'react-hot-loader/root';
import { BrowserRouter as Router, Route } from 'react-router-dom';
import AuthenticationRouter from '@/routers/AuthenticationRouter';

class App extends React.PureComponent {
    render () {
        return (
            <Router>
                <div>
                    <Route exact path="/"/>
                    <Route path="/auth" component={AuthenticationRouter}/>
                </div>
            </Router>
        );
    }
}

export default hot(App);
