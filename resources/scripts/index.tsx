import React from 'react';
import ReactDOM from 'react-dom';
import App from '@/components/App';
import './i18n';
import { setConfig } from 'react-hot-loader';

import 'tailwindcss/dist/base.min.css';

// Prevents page reloads while making component changes which
// also avoids triggering constant loading indicators all over
// the place in development.
//
// @see https://github.com/gaearon/react-hot-loader#hook-support
setConfig({ reloadHooks: false });

// Disabled this render. See below for more informations
// ReactDOM.render(<App/>, document.getElementById('app'));

// Need the StrictMode and the Suspense fallback for the i18n lazy loading
ReactDOM.render(
    <React.StrictMode>
        <React.Suspense fallback='Loading...'>
            <App />
        </React.Suspense>
    </React.StrictMode>,
    document.getElementById('app'),
);
