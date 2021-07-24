import React from 'react';
import ReactDOM from 'react-dom';
import App from '@/components/App';
import { setConfig } from 'react-hot-loader';
import Spinner from '@/components/elements/Spinner';

import '@/i18n';
import 'tailwindcss/dist/base.min.css';

// Prevents page reloads while making component changes which
// also avoids triggering constant loading indicators all over
// the place in development.
//
// @see https://github.com/gaearon/react-hot-loader#hook-support
setConfig({ reloadHooks: false });

ReactDOM.render(<Spinner.Suspense><App/></Spinner.Suspense>, document.getElementById('app'));
