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

ReactDOM.render(<App/>, document.getElementById('app'));
